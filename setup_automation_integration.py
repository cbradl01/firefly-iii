#!/usr/bin/env python3
"""Setup script for Firefly-III Automation Integration"""

from pathlib import Path


def check_environment():
    """Check if required environment variables are set"""
    print("🔧 Checking environment...")

    # Check if we're in the Firefly-III directory
    if not Path("artisan").exists():
        print("❌ This script must be run from the Firefly-III root directory")
        return False

    print("✅ Firefly-III directory detected")
    return True


def check_files_exist():
    """Check if automation files exist"""
    print("\n📄 Checking automation files...")

    required_files = [
        "public/v1/js/ff/automation/automation.js",
        "public/v1/css/automation.css",
        "resources/views/accounts/show.twig",
    ]

    missing_files = []
    for file_path in required_files:
        if not Path(file_path).exists():
            missing_files.append(file_path)
        else:
            print(f"✅ {file_path}")

    if missing_files:
        print("❌ Missing files:")
        for file_path in missing_files:
            print(f"   {file_path}")
        return False

    return True


def check_pfinance_microservice():
    """Check if PFinance microservice is accessible"""
    print("\n🔗 Checking PFinance microservice...")

    # This would typically check if the microservice is running
    # For now, we'll just provide instructions
    print("⚠️  Please ensure the PFinance microservice is running:")
    print("   cd ../pfinance/pfinance-microservice")
    print("   python run.py")

    return True


def show_integration_instructions():
    """Show instructions for the integration"""
    print("\n🎯 Integration Instructions:")
    print("\n1. The automation controls have been added to the account show page.")
    print("   Navigate to any account page to see the automation section.")

    print("\n2. The automation controls will:")
    print("   - Show automation status for the current account")
    print("   - Provide buttons for downloading statements and transactions")
    print("   - Allow configuration of supported institutions")

    print("\n3. The integration connects to the PFinance microservice API:")
    print("   - Base URL: /api/v1")
    print("   - Endpoints: /automation/*")

    print("\n4. To test the integration:")
    print("   - Start the PFinance microservice")
    print("   - Navigate to an account page in Firefly-III")
    print("   - Look for the '🤖 Automation' section")

    print("\n5. Configuration:")
    print("   - Use the '⚙️ Automation Config' button to manage institutions")
    print("   - Enable/disable automation for specific banks")
    print("   - Monitor automation status in real-time")


def show_troubleshooting():
    """Show troubleshooting information"""
    print("\n🔧 Troubleshooting:")
    print("\n1. If automation controls don't appear:")
    print("   - Check browser console for JavaScript errors")
    print("   - Ensure automation.js and automation.css are loaded")
    print("   - Verify the account page template was updated")

    print("\n2. If API calls fail:")
    print("   - Ensure PFinance microservice is running")
    print("   - Check CORS settings in the microservice")
    print("   - Verify the API base URL is correct")

    print("\n3. If automation doesn't work:")
    print("   - Check LastPass credentials are configured")
    print("   - Verify institution is enabled in configuration")
    print("   - Check automation logs for errors")

    print("\n4. Common issues:")
    print("   - CORS errors: Configure microservice to allow Firefly-III domain")
    print("   - 404 errors: Ensure automation routes are registered")
    print("   - Import errors: Check Python dependencies are installed")


def main():
    """Main setup function"""
    print("🚀 Firefly-III Automation Integration Setup")
    print("=" * 60)

    # Check environment
    if not check_environment():
        return 1

    # Check files exist
    if not check_files_exist():
        print(
            "\n❌ Some required files are missing. Please ensure all automation files are in place."
        )
        return 1

    # Check microservice
    check_pfinance_microservice()

    # Show instructions
    show_integration_instructions()

    # Show troubleshooting
    show_troubleshooting()

    print("\n🎉 Setup completed successfully!")
    print("\nNext steps:")
    print("1. Start the PFinance microservice")
    print("2. Navigate to an account page in Firefly-III")
    print("3. Test the automation controls")
    print("4. Configure your institutions")

    return 0


if __name__ == "__main__":
    exit(main())
