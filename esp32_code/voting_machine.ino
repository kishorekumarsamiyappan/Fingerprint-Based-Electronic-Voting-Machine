#include <Wire.h>
#include <U8g2lib.h>
#include <Adafruit_Fingerprint.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <Preferences.h>

// ---------- OLED ----------
U8G2_SH1106_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, U8X8_PIN_NONE);

// ---------- Fingerprint sensor (R307) ----------
#define FINGER_RX 16
#define FINGER_TX 17
HardwareSerial fingerSerial(2);
Adafruit_Fingerprint finger(&fingerSerial);

// Web server configuration
const char* serverURL = "http://192.168.85.6/voting_system/api"; // Change to your PC's IP
String currentPlace = "";

// ---------- WiFi credentials ----------
const char* WIFI_SSID = "Kishorekumar S";
const char* WIFI_PASS = "6374864213";

// ---------- Pins ----------
const int upBtn = 13;
const int downBtn = 12;
const int okBtn = 14;
const int backBtn = 27;
const int c1Btn = 26;
const int c2Btn = 25;
const int c3Btn = 33;
const int c4Btn = 32;

const int redLed = 4;
const int greenLed = 2;
const int buzzerPin = 5;

// ---------- Application state ----------
enum MenuState {
  BOOT,
  CHECK_WIFI_SENSOR,
  PLACE_SELECTION,
  MAIN_MENU,
  ENROLL_MENU,
  VOTE_MENU,
  VOTER_IDENTIFICATION,
  VOTER_DETAILS,
  CANDIDATE_SELECTION,
  CONFIRM_VOTE,
  VOTE_RECORDED,
  ADMIN_MENU,
  ADMIN_ACTION,
  RESULTS_SCREEN
};
MenuState currentState = BOOT;

const char* places[] = {"Erode","Coimbatore","Tiruppur"};
int selectedPlace = 0;
bool placeLocked = false;

const char* mainOptions[] = {"Enroll Fingerprint","Vote","Admin Login"};
int mainSelected = 0;

const char* candidates[] = {"Candidate 1","Candidate 2","Candidate 3","Candidate 4"};
int selectedCandidate = 0;

const char* adminOptions[] = {"Show Results","Delete Voter Fingers","Reset System"};
int adminSelected = 0;

unsigned long lastPress = 0;
const unsigned long DEBOUNCE = 200;

// ---------- Heartbeat ----------
unsigned long lastHeartbeat = 0;
const unsigned long HEARTBEAT_INTERVAL = 30000; // 30 seconds

// Preferences for persistent storage
Preferences preferences;

// enrollment tracking - will be loaded from preferences based on place
int lastEnrollID = 1;

// votes
int votesArr[4] = {0,0,0,0};

// System status
bool wifiConnected = false;
bool sensorConnected = false;
String wifiStatus = "Disconnected";
String sensorStatus = "Disconnected";

// Voter details storage
String voterName = "";
String voterDOB = "";
String voterAadhaar = "";
String voterID = "";
int currentVoterFingerprintID = -1;

// ---------- Function Declarations ----------
void sendVoteToServer(int fingerprint_id, int candidate_id);
void sendEnrollmentToServer(int fingerprint_id);
bool getVoterDetailsFromServer(int fingerprint_id);
bool checkIfVoterAlreadyVoted(int fingerprint_id);
void sendHeartbeatToServer();
void enterAdminMenu();
void showAdminSummaryOnSerial();
void deleteAllVoterFingers();
void resetSystemKeepAdmin();
void checkWiFiStatus();
void checkSensorStatus();
void loadLastEnrollID();
void saveLastEnrollID();
int findNextFreeID();

// ---------- Persistent Storage Functions ----------
void loadLastEnrollID() {
  preferences.begin("voting-system", false);
  String key = "lastID_" + currentPlace;
  lastEnrollID = preferences.getInt(key.c_str(), 1);
  Serial.print("[STORAGE] Loaded lastEnrollID for ");
  Serial.print(currentPlace);
  Serial.print(": ");
  Serial.println(lastEnrollID);
  preferences.end();
}

void saveLastEnrollID() {
  preferences.begin("voting-system", false);
  String key = "lastID_" + currentPlace;
  preferences.putInt(key.c_str(), lastEnrollID);
  Serial.print("[STORAGE] Saved lastEnrollID for ");
  Serial.print(currentPlace);
  Serial.print(": ");
  Serial.println(lastEnrollID);
  preferences.end();
}

// ---------- Helpers ----------
void blinkGreen(int times=1){
  for(int i=0;i<times;i++){
    digitalWrite(greenLed,HIGH);
    tone(buzzerPin,1000,180);
    delay(200);
    digitalWrite(greenLed,LOW);
    noTone(buzzerPin);
    delay(80);
  }
}

void blinkRedOnceNoBuzzer(int ms = 200){
  digitalWrite(redLed,HIGH);
  delay(ms);
  digitalWrite(redLed,LOW);
}

void blinkRedWithBuzzer(int times=1){
  for(int i=0;i<times;i++){
    digitalWrite(redLed,HIGH);
    tone(buzzerPin,500,180);
    delay(200);
    digitalWrite(redLed,LOW);
    noTone(buzzerPin);
    delay(80);
  }
}

void showOLEDLines(const char* l1, const char* l2 = "", const char* l3 = "", const char* l4 = "") {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_ncenB08_tr);
  if(strlen(l1)) u8g2.drawStr(0,12,l1);
  if(strlen(l2)) u8g2.drawStr(0,28,l2);
  if(strlen(l3)) u8g2.drawStr(0,44,l3);
  if(strlen(l4)) u8g2.drawStr(0,60,l4);
  u8g2.sendBuffer();
}

// Overloaded function to handle String parameters
void showOLEDLines(String l1, String l2 = "", String l3 = "", String l4 = "") {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_ncenB08_tr);
  if(l1.length()) u8g2.drawStr(0,12,l1.c_str());
  if(l2.length()) u8g2.drawStr(0,28,l2.c_str());
  if(l3.length()) u8g2.drawStr(0,44,l3.c_str());
  if(l4.length()) u8g2.drawStr(0,60,l4.c_str());
  u8g2.sendBuffer();
}

void displayError(const char* a, const char* b=""){
  showOLEDLines(a,b);
  blinkRedWithBuzzer(2);
}

void logTemplateInfo(){
  finger.getTemplateCount();
  Serial.print("[INFO] templateCount (count): ");
  Serial.println(finger.templateCount);
  Serial.print("[INFO] lastEnrollID (tracked): ");
  Serial.println(lastEnrollID);
}

int findNextFreeID(){
  // Start from lastEnrollID + 1 and find the next available slot
  for(int id = lastEnrollID + 1; id <= 127; id++){
    if(finger.loadModel(id) != FINGERPRINT_OK) {
      // This ID is available (load failed means no template exists)
      return id;
    }
  }
  return -1; // No free slots
}

// ---------- Status Checking ----------
void checkWiFiStatus() {
  bool previousStatus = wifiConnected;
  wifiConnected = (WiFi.status() == WL_CONNECTED);
  wifiStatus = wifiConnected ? "Connected" : "Disconnected";
  
  if(wifiConnected != previousStatus) {
    Serial.print("[STATUS] WiFi: ");
    Serial.println(wifiStatus);
  }
}

void checkSensorStatus() {
  bool previousStatus = sensorConnected;
  sensorConnected = finger.verifyPassword();
  sensorStatus = sensorConnected ? "Connected" : "Disconnected";
  
  if(sensorConnected != previousStatus) {
    Serial.print("[STATUS] Sensor: ");
    Serial.println(sensorStatus);
  }
}

// ---------- Heartbeat Function ----------
void sendHeartbeatToServer() {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    String url = String(serverURL) + "/heartbeat.php";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    
    String jsonPayload = "{";
    jsonPayload += "\"place\":\"" + currentPlace + "\",";
    jsonPayload += "\"wifi_status\":\"" + wifiStatus + "\",";
    jsonPayload += "\"sensor_status\":\"" + sensorStatus + "\",";
    jsonPayload += "\"template_count\":" + String(finger.templateCount) + ",";
    jsonPayload += "\"ip_address\":\"" + WiFi.localIP().toString() + "\",";
    jsonPayload += "\"last_enroll_id\":" + String(lastEnrollID) + ",";
    jsonPayload += "\"votes_total\":" + String(votesArr[0] + votesArr[1] + votesArr[2] + votesArr[3]);
    jsonPayload += "}";
    
    int httpResponseCode = http.POST(jsonPayload);
    
    if(httpResponseCode == 200){
      Serial.println("[HEARTBEAT] Status sent to server");
    } else {
      Serial.print("[HEARTBEAT] Error sending status: ");
      Serial.println(httpResponseCode);
    }
    
    http.end();
  } else {
    Serial.println("[HEARTBEAT] WiFi not connected, skipping heartbeat");
  }
}

// ---------- Check if Voter Already Voted ----------
bool checkIfVoterAlreadyVoted(int fingerprint_id) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    String url = String(serverURL) + "/check_vote_status.php?fingerprint_id=" + String(fingerprint_id);
    http.begin(url);
    
    int httpResponseCode = http.GET();
    
    if(httpResponseCode == 200){
      String response = http.getString();
      Serial.println("[WEB] Vote status: " + response);
      
      // Parse JSON response
      if(response.indexOf("\"has_voted\":true") != -1) {
        http.end();
        return true;
      }
    }
    http.end();
  }
  return false;
}

// ---------- Get Voter Details Function ----------
bool getVoterDetailsFromServer(int fingerprint_id) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    String url = String(serverURL) + "/get_voter_details.php?fingerprint_id=" + String(fingerprint_id);
    http.begin(url);
    
    int httpResponseCode = http.GET();
    
    if(httpResponseCode == 200){
      String response = http.getString();
      Serial.println("[WEB] Voter details received: " + response);
      
      // Check if voter exists and is completed
      if(response.indexOf("\"status\":\"error\"") != -1) {
        http.end();
        return false;
      }
      
      // Parse JSON response
      int nameStart = response.indexOf("\"name\":\"") + 8;
      int nameEnd = response.indexOf("\"", nameStart);
      voterName = response.substring(nameStart, nameEnd);
      
      int dobStart = response.indexOf("\"dob\":\"") + 7;
      int dobEnd = response.indexOf("\"", dobStart);
      voterDOB = response.substring(dobStart, dobEnd);
      
      int aadhaarStart = response.indexOf("\"aadhaar\":\"") + 11;
      int aadhaarEnd = response.indexOf("\"", aadhaarStart);
      voterAadhaar = response.substring(aadhaarStart, aadhaarEnd);
      
      int voterIdStart = response.indexOf("\"voter_id\":\"") + 12;
      int voterIdEnd = response.indexOf("\"", voterIdStart);
      voterID = response.substring(voterIdStart, voterIdEnd);
      
      http.end();
      return true;
    } else {
      Serial.print("[WEB] Error getting voter details: ");
      Serial.println(httpResponseCode);
      http.end();
      return false;
    }
  } else {
    Serial.println("[WEB] WiFi not connected, cannot get voter details");
    return false;
  }
}

// ---------- WiFi & Sensor check ----------
void checkWiFiSensor() {
  while (true) {
    bool wifiOK = false;
    bool sensorOK = false;

    // WiFi check
    Serial.println("[CHECK] Starting WiFi connect loop...");
    WiFi.mode(WIFI_STA);
    WiFi.disconnect(true);
    delay(100);
    WiFi.begin(WIFI_SSID, WIFI_PASS);

    unsigned long wifiStartTime = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - wifiStartTime < 15000) {
      digitalWrite(redLed, HIGH);
      tone(buzzerPin, 600, 250);
      showOLEDLines("WiFi Connecting", "Please wait...");
      delay(500);
      digitalWrite(redLed, LOW);
      noTone(buzzerPin);
      delay(500);
      
      if (WiFi.status() == WL_CONNECTED) break;
    }
    
    if(WiFi.status() == WL_CONNECTED) {
      wifiOK = true;
      wifiConnected = true;
      wifiStatus = "Connected";
      Serial.print("[INFO] WiFi connected. IP: ");
      Serial.println(WiFi.localIP());
      blinkGreen(1);
    } else {
      Serial.println("[WARN] WiFi connection timeout");
    }

    // Sensor check
    Serial.println("[CHECK] Verifying fingerprint sensor...");
    unsigned long sensorStartTime = millis();
    while (millis() - sensorStartTime < 10000) {
      if (finger.verifyPassword()) {
        sensorOK = true;
        sensorConnected = true;
        sensorStatus = "Connected";
        break;
      }
      digitalWrite(redLed, HIGH);
      tone(buzzerPin, 600, 250);
      showOLEDLines("Sensor Checking", "Please wait...");
      delay(500);
      digitalWrite(redLed, LOW);
      noTone(buzzerPin);
      delay(500);
    }

    if (wifiOK && sensorOK) {
      showOLEDLines("All Systems OK!", "", "", "");
      blinkGreen(2);
      delay(1000);
      return;
    }

    // Show what failed
    u8g2.clearBuffer();
    u8g2.setFont(u8g2_font_ncenB08_tr);
    u8g2.drawStr(0,12,"Connection Failed:");
    if(!wifiOK) u8g2.drawStr(0,28,"WiFi: Failed");
    if(!sensorOK) u8g2.drawStr(0,44,"Sensor: Failed");
    u8g2.drawStr(0,60,"Press OK to retry");
    u8g2.sendBuffer();
    blinkRedWithBuzzer(2); 

    while (digitalRead(okBtn) == HIGH) delay(80);
    while (digitalRead(okBtn) == LOW) delay(80);
  }
}

// ---------- Boot ----------
void bootScreen(){
  showOLEDLines("Fingerprint-based","Voting Machine","Booting...","");
  delay(1500);
}

// ---------- Finger utility ----------
int getFingerprintID_interactive(){
  while(true){
    showOLEDLines("Place finger","to scan","","");
    int p = finger.getImage();
    if(p == FINGERPRINT_OK){
      if(finger.image2Tz() != FINGERPRINT_OK){
        displayError("Image->TZ failed","");
      } else {
        if(finger.fingerFastSearch() == FINGERPRINT_OK){
          return finger.fingerID;
        } else {
          u8g2.clearBuffer();
          u8g2.drawStr(0,12,"No match or error");
          u8g2.drawStr(0,28,"Press OK to retry");
          u8g2.drawStr(0,44,"BACK to cancel");
          u8g2.sendBuffer();
          blinkRedWithBuzzer(1);
          while(digitalRead(okBtn) == HIGH && digitalRead(backBtn) == HIGH) delay(80);
          if(digitalRead(backBtn) == LOW){
            while(digitalRead(backBtn) == LOW) delay(80);
            return -1;
          }
          while(digitalRead(okBtn) == LOW) delay(80);
          continue;
        }
      }
    } else {
      u8g2.clearBuffer();
      u8g2.drawStr(0,12,"No finger detected");
      u8g2.drawStr(0,28,"Press OK to retry");
      u8g2.drawStr(0,44,"BACK to cancel");
      u8g2.sendBuffer();
      while(digitalRead(okBtn) == HIGH && digitalRead(backBtn) == HIGH) delay(80);
      if(digitalRead(backBtn) == LOW){
        while(digitalRead(backBtn) == LOW) delay(80);
        return -1;
      }
      while(digitalRead(okBtn) == LOW) delay(80);
    }
  }
}

int getFingerprintID_safe(){
  int p = finger.getImage();
  if(p != FINGERPRINT_OK) return -1;
  p = finger.image2Tz();
  if(p != FINGERPRINT_OK) return -1;
  p = finger.fingerFastSearch();
  if(p != FINGERPRINT_OK) return -1;
  return finger.fingerID;
}

// ---------- Web Server Functions ----------
void sendVoteToServer(int fingerprint_id, int candidate_id) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    String url = String(serverURL) + "/vote.php";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    
    String jsonPayload = "{";
    jsonPayload += "\"fingerprint_id\":" + String(fingerprint_id) + ",";
    jsonPayload += "\"candidate_id\":" + String(candidate_id + 1) + ",";
    jsonPayload += "\"place\":\"" + currentPlace + "\"";
    jsonPayload += "}";
    
    int httpResponseCode = http.POST(jsonPayload);
    
    if(httpResponseCode == 200){
      Serial.println("[WEB] Vote sent to server successfully");
    } else {
      Serial.print("[WEB] Error sending vote: ");
      Serial.println(httpResponseCode);
    }
    
    http.end();
  } else {
    Serial.println("[WEB] WiFi not connected, vote not sent to server");
  }
}

void sendEnrollmentToServer(int fingerprint_id) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    String url = String(serverURL) + "/enroll_fingerprint.php";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    
    String jsonPayload = "{";
    jsonPayload += "\"fingerprint_id\":" + String(fingerprint_id) + ",";
    jsonPayload += "\"place\":\"" + currentPlace + "\"";
    jsonPayload += "}";
    
    int httpResponseCode = http.POST(jsonPayload);
    
    if(httpResponseCode == 200){
      Serial.println("[WEB] Enrollment info sent to server");
    } else {
      Serial.print("[WEB] Error sending enrollment: ");
      Serial.println(httpResponseCode);
    }
    
    http.end();
  } else {
    Serial.println("[WEB] WiFi not connected, enrollment not sent to server");
  }
}

// ---------- Serial Admin commands ----------
void handleSerialCommand(String s){
  s.trim();
  s.toUpperCase();
  if(s.startsWith("ENR")){
    int id = s.length() > 3 ? s.substring(3).toInt() : -1;
    if(id == 1){
      Serial.println("[SERIAL] Admin enrollment requested (ID 1). Place admin finger.");
      showOLEDLines("Serial: Admin Enroll","Place finger now","","");
      while(finger.getImage() != FINGERPRINT_OK) delay(80);
      finger.image2Tz(1);
      showOLEDLines("Serial: Admin Enroll","Remove finger","","");
      delay(1200);
      showOLEDLines("Serial: Admin Enroll","Place again","","");
      while(finger.getImage() != FINGERPRINT_OK) delay(80);
      finger.image2Tz(2);
      if(finger.createModel() == FINGERPRINT_OK && finger.storeModel(1) == FINGERPRINT_OK){
        Serial.println("[SERIAL] Admin enrolled ID 1 successfully");
        showOLEDLines("Admin Enrolled!","ID: 1","","");
        blinkGreen(2);
      } else {
        Serial.println("[SERIAL] Admin enrollment failed");
        displayError("Admin enroll failed","");
      }
    } else {
      Serial.println("[SERIAL] Unknown ENR command. Use: ENR 1");
    }
  } else if(s.startsWith("DEL")){
    int id = s.length() > 3 ? s.substring(3).toInt() : -1;
    if(id >= 1 && id <= 127){
      if(id == 1){
        if(finger.deleteModel(1) == FINGERPRINT_OK){
          Serial.println("[SERIAL] Admin fingerprint deleted (ID 1).");
          showOLEDLines("Serial: Admin Deleted","ID 1","","");
          blinkGreen(2);
        } else {
          Serial.println("[SERIAL] Failed to delete Admin ID 1.");
          displayError("Del Admin failed","");
        }
      } else {
        if(finger.deleteModel(id) == FINGERPRINT_OK){
          Serial.print("[SERIAL] Deleted ID: "); Serial.println(id);
          char buf[32];
          sprintf(buf,"Deleted ID: %d", id);
          showOLEDLines("Serial: Deleted ID", buf,"","");
          blinkGreen(1);
        } else {
          Serial.print("[SERIAL] Failed to delete ID: "); Serial.println(id);
          displayError("Del failed", "");
        }
      }
    } else {
      Serial.println("[SERIAL] Invalid DEL command. Use: DEL <ID>");
    }
  } else if(s == "INFO"){
    finger.getTemplateCount();
    Serial.print("[SERIAL] Template count: ");
    Serial.println(finger.templateCount);
    Serial.print("[SERIAL] lastEnrollID (tracked): ");
    Serial.println(lastEnrollID);
    Serial.print("[SERIAL] Current Place: ");
    Serial.println(currentPlace);
    Serial.print("[SERIAL] WiFi Status: ");
    Serial.println(wifiStatus);
    Serial.print("[SERIAL] Sensor Status: ");
    Serial.println(sensorStatus);
    showOLEDLines("Serial Info:","Check Serial Monitor","","");
  } else if(s == "STATUS"){
    Serial.println("=== SYSTEM STATUS ===");
    Serial.print("WiFi: "); Serial.println(wifiStatus);
    Serial.print("Sensor: "); Serial.println(sensorStatus);
    Serial.print("IP: "); Serial.println(WiFi.localIP());
    Serial.print("Templates: "); Serial.println(finger.templateCount);
    Serial.print("Current Place: "); Serial.println(currentPlace);
    Serial.print("Last Enroll ID: "); Serial.println(lastEnrollID);
    Serial.println("===================");
  } else if(s == "RESETIDS"){
    // Reset all lastEnrollIDs for all places
    preferences.begin("voting-system", false);
    preferences.clear();
    preferences.end();
    lastEnrollID = 1;
    Serial.println("[SERIAL] All stored IDs reset to 1");
    showOLEDLines("All IDs Reset","to 1","","");
    blinkGreen(2);
  } else {
    Serial.println("[SERIAL] Unknown command. Supported: ENR 1 | DEL <id> | INFO | STATUS | RESETIDS");
  }
}

// ---------- Enrollment (voter) ----------
void enrollVoter(){
  showOLEDLines("Place finger for","duplicate check","","");
  int p = finger.getImage();
  if(p == FINGERPRINT_OK){
    p = finger.image2Tz();
    if(p == FINGERPRINT_OK){
      p = finger.fingerFastSearch();
      if(p == FINGERPRINT_OK){
        int foundID = finger.fingerID;
        Serial.print("[ENROLL] Duplicate finger. ID exists: "); Serial.println(foundID);
        showOLEDLines("Duplicate Finger!","Already enrolled","","");
        blinkRedWithBuzzer(2);
        delay(1500);
        return;
      }
    }
  }
  
  int id = findNextFreeID();
  if(id == -1){
    Serial.println("[ENROLL] No space for new templates.");
    displayError("No space for","enrollment");
    return;
  }

  Serial.print("[ENROLL] Enrolling new voter with ID: "); Serial.println(id);
  
  showOLEDLines("First scan:","Place finger","(Hold steady)","");
  while(true){
    int r = finger.getImage();
    if(r == FINGERPRINT_OK) break;
    if(digitalRead(backBtn) == LOW){
      while(digitalRead(backBtn) == LOW) delay(80);
      showOLEDLines("Enrollment","Cancelled","","");
      return;
    }
    delay(80);
  }
  
  if(finger.image2Tz(1) != FINGERPRINT_OK){
    displayError("Image->TZ failed","");
    blinkRedWithBuzzer(1);
    return;
  }

  showOLEDLines("Remove finger","then place again","","");
  delay(1200);

  showOLEDLines("Second scan:","Place same finger","","");
  while(true){
    int r = finger.getImage();
    if(r == FINGERPRINT_OK) break;
    if(digitalRead(backBtn) == LOW){
      while(digitalRead(backBtn) == LOW) delay(80);
      showOLEDLines("Enrollment","Cancelled","","");
      return;
    }
    delay(80);
  }
  
  if(finger.image2Tz(2) != FINGERPRINT_OK){
    displayError("Image->TZ failed","");
    blinkRedWithBuzzer(1);
    return;
  }

  if(finger.createModel() != FINGERPRINT_OK){
    displayError("Create model failed","");
    blinkRedWithBuzzer(1);
    return;
  }
  
  if(finger.storeModel(id) != FINGERPRINT_OK){
    displayError("Store model failed","");
    blinkRedWithBuzzer(1);
    return;
  }

  // SUCCESS - Update lastEnrollID and save to persistent storage
  lastEnrollID = id;
  saveLastEnrollID();
  
  Serial.print("[ENROLL] Voter enrolled, ID: "); Serial.println(id);
 
  // Send enrollment to web server
  sendEnrollmentToServer(id);
  
  char idbuf[24]; sprintf(idbuf, "ID: %d", id);
  showOLEDLines("Enrollment Success!", idbuf, "", "");
  blinkGreen(2);
  delay(1000);
  finger.getTemplateCount();
  Serial.print("[ENROLL] templateCount now: "); Serial.println(finger.templateCount);
}

// ---------- Admin menu ----------
void enterAdminMenu(){
  adminSelected = 0;
  currentState = ADMIN_MENU;
}

void showAdminSummaryOnSerial(){
  finger.getTemplateCount();
  Serial.println("-------- ADMIN SUMMARY --------");
  Serial.print("Template count (count): "); Serial.println(finger.templateCount);
  Serial.print("Current Place: "); Serial.println(currentPlace);
  Serial.print("Last Enroll ID: "); Serial.println(lastEnrollID);
  Serial.print("WiFi Status: "); Serial.println(wifiStatus);
  Serial.print("Sensor Status: "); Serial.println(sensorStatus);
  Serial.println("Enrolled IDs (approx): admin=1, voters up to tracked ID");
  Serial.println("Votes:");
  for(int i=0;i<4;i++){
    Serial.print("Candidate "); Serial.print(i+1); Serial.print(": "); Serial.println(votesArr[i]);
  }
  Serial.println("-------------------------------");
}

void deleteAllVoterFingers(){
  Serial.println("[ADMIN] Deleting all voter fingerprints (IDs >= 2)...");
  int deleted = 0;
  for(int id = 2; id <= 127; ++id){
    if(finger.deleteModel(id) == FINGERPRINT_OK){
      Serial.print("[ADMIN] Deleted ID: "); Serial.println(id);
      deleted++;
    }
  }
  // Reset lastEnrollID for current place
  lastEnrollID = 1;
  saveLastEnrollID();
  finger.getTemplateCount();
  Serial.print("[ADMIN] Done. Deleted count (attempted): ");
  Serial.println(deleted);
  showOLEDLines("All voter fingers","deleted","","");
  blinkGreen(2);
}

void resetSystemKeepAdmin(){
  deleteAllVoterFingers();
  for(int i=0;i<4;i++) votesArr[i] = 0;
  Serial.println("[ADMIN] System reset complete (admin preserved unless DEL 1 used).");
  showOLEDLines("System Reset Done","Admin preserved","","");
  blinkGreen(2);
}

// ---------- Setup & loop ----------
void setup(){
  Serial.begin(115200);
  delay(50);
  u8g2.begin();
  pinMode(upBtn, INPUT_PULLUP);
  pinMode(downBtn, INPUT_PULLUP);
  pinMode(okBtn, INPUT_PULLUP);
  pinMode(backBtn, INPUT_PULLUP);
  pinMode(c1Btn, INPUT_PULLUP);
  pinMode(c2Btn, INPUT_PULLUP);
  pinMode(c3Btn, INPUT_PULLUP);
  pinMode(c4Btn, INPUT_PULLUP);
  pinMode(redLed, OUTPUT); digitalWrite(redLed, LOW);
  pinMode(greenLed, OUTPUT); digitalWrite(greenLed, LOW);
  pinMode(buzzerPin, OUTPUT); noTone(buzzerPin);

  // Initialize preferences
  preferences.begin("voting-system", false);

  bootScreen();
  fingerSerial.begin(57600, SERIAL_8N1, FINGER_RX, FINGER_TX);
  delay(200);

  if(!finger.verifyPassword()){
    Serial.println("[SETUP] Fingerprint sensor not found or bad connection!");
    displayError("Sensor not found","Check wiring");
    sensorConnected = false;
    sensorStatus = "Disconnected";
  } else {
    sensorConnected = true;
    sensorStatus = "Connected";
  }
  
  finger.getTemplateCount();
  Serial.print("[SETUP] templateCount (count): "); Serial.println(finger.templateCount);

  currentState = CHECK_WIFI_SENSOR;
  checkWiFiSensor();

  currentState = PLACE_SELECTION;
}

void loop(){
  if(Serial.available()){
    String s = Serial.readStringUntil('\n');
    handleSerialCommand(s);
    delay(100);
  }

  unsigned long now = millis();

  // Check system status periodically
  checkWiFiStatus();
  checkSensorStatus();

  // Send heartbeat every 30 seconds
  if (now - lastHeartbeat > HEARTBEAT_INTERVAL && currentPlace != "") {
    lastHeartbeat = now;
    sendHeartbeatToServer();
  }

  switch(currentState){
    case BOOT:
      bootScreen();
      currentState = CHECK_WIFI_SENSOR;
      break;

    case CHECK_WIFI_SENSOR:
      checkWiFiSensor();
      currentState = PLACE_SELECTION;
      break;

    case PLACE_SELECTION:
      if(now - lastPress > DEBOUNCE){
        if(digitalRead(upBtn) == LOW){ selectedPlace = (selectedPlace + 2) % 3; lastPress = now; }
        else if(digitalRead(downBtn) == LOW){ selectedPlace = (selectedPlace + 1) % 3; lastPress = now; }
        else if(digitalRead(okBtn) == LOW){
          lastPress = now;
          placeLocked = true;
          currentPlace = places[selectedPlace];
          
          // Load the lastEnrollID for the selected place
          loadLastEnrollID();
          
          currentState = MAIN_MENU;
          mainSelected = 0;
          // Send initial heartbeat after place selection
          sendHeartbeatToServer();
        }
      }
      u8g2.clearBuffer();
      u8g2.drawStr(0,12,"Select Place (One-time):");
      for(int i=0;i<3;i++){
        if(i == selectedPlace) u8g2.drawStr(0,28 + i*12, ">");
        u8g2.drawStr(10, 28 + i*12, places[i]);
      }
      u8g2.sendBuffer();
      break;

    case MAIN_MENU:
      if(now - lastPress > DEBOUNCE){
        if(digitalRead(upBtn) == LOW){ mainSelected = (mainSelected + 2) % 3; lastPress = now; }
        else if(digitalRead(downBtn) == LOW){ mainSelected = (mainSelected + 1) % 3; lastPress = now; }
        else if(digitalRead(okBtn) == LOW){
          lastPress = now;
          if(mainSelected == 0){ currentState = ENROLL_MENU; }
          else if(mainSelected == 1){ currentState = VOTE_MENU; }
          else if(mainSelected == 2){
            showOLEDLines("Admin Login","Scan admin finger","","");
            int fid = getFingerprintID_interactive();
            if(fid == 1){ 
              Serial.println("[ADMIN] Admin scanned. Entering admin menu."); 
              enterAdminMenu(); 
            }
            else if(fid == -1){
              showOLEDLines("Admin Login","Cancelled","","");
              delay(800);
              currentState = MAIN_MENU;
            } else {
              showOLEDLines("Not Admin","Access Denied","","");
              blinkRedWithBuzzer(1); delay(1000); currentState = MAIN_MENU;
            }
          }
        }
      }
      u8g2.clearBuffer();
      char bufPlace[32];
      sprintf(bufPlace,"Place: %s", places[selectedPlace]);
      u8g2.drawStr(0,10, bufPlace);
      
      u8g2.drawStr(0,25, "Select Option:");
      for(int i=0;i<3;i++){
        if(i == mainSelected) u8g2.drawStr(0,40 + i*12, ">");
        u8g2.drawStr(10,40 + i*12, mainOptions[i]);
      }
      u8g2.sendBuffer();
      break;

    case ENROLL_MENU:
      showOLEDLines("Voter Enrollment","Press OK to start","","");
      if(now - lastPress > DEBOUNCE){
        if(digitalRead(okBtn) == LOW){ lastPress = now; enrollVoter(); }
        else if(digitalRead(backBtn) == LOW){ lastPress = now; currentState = MAIN_MENU; }
      }
      break;

    case VOTE_MENU:
      showOLEDLines("Voting Mode","Press OK to continue","","");
      if(now - lastPress > DEBOUNCE){
        if(digitalRead(okBtn) == LOW){ 
          lastPress = now; 
          currentState = VOTER_IDENTIFICATION; 
        }
        else if(digitalRead(backBtn) == LOW){ lastPress = now; currentState = MAIN_MENU; }
      }
      break;

    // Voter Identification State
    case VOTER_IDENTIFICATION:
      {
        showOLEDLines("Scan Fingerprint","to identify","","");
        int fid = getFingerprintID_interactive();
        if(fid == -1){
          showOLEDLines("Identification","Cancelled","","");
          blinkRedWithBuzzer(1); delay(900);
          currentState = VOTE_MENU;
        } else if(fid == 1){
          showOLEDLines("Admin cannot vote","","","");
          blinkRedWithBuzzer(1); delay(900);
          currentState = MAIN_MENU;
        } else {
          // CHECK IF ALREADY VOTED
          showOLEDLines("Checking vote","status...","","");
          if(checkIfVoterAlreadyVoted(fid)) {
            showOLEDLines("Already Voted!", "You cannot vote", "again", "");
            blinkRedWithBuzzer(3); // Triple red blink and buzzer
            delay(3000);
            currentState = MAIN_MENU;
            break;
          }
          
          currentVoterFingerprintID = fid;
          Serial.print("[VOTE] Voter identified, ID: "); Serial.println(fid);
          
          // Get voter details from server
          showOLEDLines("Fetching voter","details...","","");
          if(getVoterDetailsFromServer(fid)) {
            currentState = VOTER_DETAILS;
          } else {
            showOLEDLines("Voter not found","or incomplete","registration","");
            blinkRedWithBuzzer(1); delay(1500);
            currentState = VOTE_MENU;
          }
        }
      }
      break;

    // Voter Details State
    case VOTER_DETAILS:
      {
        // Display voter details for 3 seconds
        u8g2.clearBuffer();
        u8g2.setFont(u8g2_font_ncenB08_tr);
        u8g2.drawStr(0,8, "Voter Details:");
        u8g2.drawStr(0,20, ("Name: " + voterName.substring(0, 12)).c_str());
        u8g2.drawStr(0,32, ("DOB: " + voterDOB).c_str());
        u8g2.drawStr(0,44, ("Aadhaar: " + voterAadhaar.substring(0, 8)).c_str());
        u8g2.drawStr(0,56, ("Voter ID: " + voterID.substring(0, 8)).c_str());
        u8g2.sendBuffer();
        
        unsigned long startTime = millis();
        while(millis() - startTime < 3000) {
          if(digitalRead(okBtn) == LOW || digitalRead(backBtn) == LOW) {
            break;
          }
          delay(80);
        }
        
        currentState = CANDIDATE_SELECTION;
        selectedCandidate = 0;
      }
      break;

    case CANDIDATE_SELECTION:
      if(now - lastPress > DEBOUNCE){
        if(digitalRead(c1Btn) == LOW){ selectedCandidate = 0; lastPress = now; currentState = CONFIRM_VOTE; }
        else if(digitalRead(c2Btn) == LOW){ selectedCandidate = 1; lastPress = now; currentState = CONFIRM_VOTE; }
        else if(digitalRead(c3Btn) == LOW){ selectedCandidate = 2; lastPress = now; currentState = CONFIRM_VOTE; }
        else if(digitalRead(c4Btn) == LOW){ selectedCandidate = 3; lastPress = now; currentState = CONFIRM_VOTE; }
        else if(digitalRead(backBtn) == LOW){ lastPress = now; currentState = VOTE_MENU; }
      }
      u8g2.clearBuffer();
      u8g2.drawStr(0,10,"Select Candidate:");
      for(int i=0;i<4;i++){
        char cb[32]; sprintf(cb, "%d: %s", i+1, candidates[i]);
        u8g2.drawStr(0, 25 + i*10, cb);
      }
      u8g2.sendBuffer();
      break;

    case CONFIRM_VOTE:
      {
        char cbuf[32]; 
        sprintf(cbuf, "Confirm: %s", candidates[selectedCandidate]);
        showOLEDLines(cbuf, "OK: Confirm", "BACK: Cancel", "");
        
        if(now - lastPress > DEBOUNCE){
            if(digitalRead(okBtn) == LOW){ 
                lastPress = now; 
                
                // Record the vote locally
                votesArr[selectedCandidate]++;
                
                // Send vote to web server
                sendVoteToServer(currentVoterFingerprintID, selectedCandidate);
                
                Serial.print("[VOTE] Vote recorded for candidate "); 
                Serial.print(selectedCandidate+1);
                Serial.print(" by ID "); Serial.println(currentVoterFingerprintID);
                
                currentState = VOTE_RECORDED;
            }
            else if(digitalRead(backBtn) == LOW){ 
                lastPress = now; 
                currentState = CANDIDATE_SELECTION; 
            }
        }
      }
      break;

    // Vote Recorded State
    case VOTE_RECORDED:
      {
        showOLEDLines("Vote Recorded!","Thank you","","");
        blinkGreen(2); 
        delay(2000);
        currentState = MAIN_MENU;
        
        // Reset voter details
        voterName = "";
        voterDOB = "";
        voterAadhaar = "";
        voterID = "";
        currentVoterFingerprintID = -1;
      }
      break;

    case ADMIN_MENU:
      if(now - lastPress > DEBOUNCE){
        if(digitalRead(upBtn) == LOW){ adminSelected = (adminSelected + 2) % 3; lastPress = now; }
        else if(digitalRead(downBtn) == LOW){ adminSelected = (adminSelected + 1) % 3; lastPress = now; }
        else if(digitalRead(okBtn) == LOW){ lastPress = now; currentState = ADMIN_ACTION; }
        else if(digitalRead(backBtn) == LOW){ lastPress = now; currentState = MAIN_MENU; }
      }
      finger.getTemplateCount();
      {
        char tbuf[32];
        sprintf(tbuf,"Templates: %d", finger.templateCount);
        
        u8g2.clearBuffer();
        u8g2.drawStr(0,8,"Admin Menu");
        u8g2.drawStr(0,20,tbuf);
        
        for(int i=0;i<3;i++){
          if(i==adminSelected) u8g2.drawStr(0,34 + i*12, ">");
          u8g2.drawStr(10,34 + i*12, adminOptions[i]);
        }
        u8g2.sendBuffer();
      }
      break;

    case ADMIN_ACTION:
      if(adminSelected == 0){
        Serial.println("[ADMIN] Show results requested");
        showAdminSummaryOnSerial();
        currentState = RESULTS_SCREEN;
      } else if(adminSelected == 1){
        Serial.println("[ADMIN] Delete voter fingerprints (IDs>=2) requested");
        showOLEDLines("Deleting voter","fingerprints...","","");
        deleteAllVoterFingers();
        currentState = ADMIN_MENU;
      } else if(adminSelected == 2){
        Serial.println("[ADMIN] Reset system requested");
        showOLEDLines("Resetting system","(voters removed)","","");
        resetSystemKeepAdmin();
        currentState = ADMIN_MENU;
      } else {
        currentState = ADMIN_MENU;
      }
      break;

    case RESULTS_SCREEN:
      {
        u8g2.clearBuffer();
        u8g2.drawStr(0,8,"Results (Totals):");
        for(int i=0;i<4;i++){
          char rbuf[32];
          sprintf(rbuf,"%d: %s: %d", i+1, candidates[i], votesArr[i]);
          u8g2.drawStr(0, 24 + i*10, rbuf);
        }
        u8g2.sendBuffer();
        unsigned long start = millis();
        while(millis() - start < 4000){
          if(digitalRead(backBtn) == LOW) break;
          delay(80);
        }

        int maxv = -1; int win = -1;
        for(int i=0;i<4;i++){
          if(votesArr[i] > maxv){ maxv = votesArr[i]; win = i; }
        }
        u8g2.clearBuffer();
        if(win >= 0){
          char wbuf1[32]; sprintf(wbuf1,"Winner:");
          char wbuf2[32]; sprintf(wbuf2,"%s", candidates[win]);
          char wbuf3[32]; sprintf(wbuf3,"Votes: %d", votesArr[win]);
          u8g2.drawStr(0,18,wbuf1);
          u8g2.drawStr(0,36,wbuf2);
          u8g2.drawStr(0,54,wbuf3);
        } else {
          u8g2.drawStr(0,28,"No votes yet");
        }
        u8g2.sendBuffer();
        start = millis();
        while(millis() - start < 3000){
          if(digitalRead(backBtn) == LOW) break;
          delay(80);
        }

        currentState = ADMIN_MENU;
      }
      break;
  }

  delay(10);
}