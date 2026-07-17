#!/bin/bash

# Auto Featured Image Plugin Test Runner
# Runs PHPUnit tests for the plugin

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
WP_VERSION=${WP_VERSION:-latest}
WP_MULTISITE=${WP_MULTISITE:-0}
PHP_VERSION=${PHP_VERSION:-7.4}
COVERAGE=${COVERAGE:-false}
SUITE=${SUITE:-all}
VERBOSE=${VERBOSE:-false}

# Plugin directory
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TESTS_DIR="$PLUGIN_DIR/tests"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -s, --suite SUITE       Test suite to run (unit|integration|performance|all)"
    echo "  -c, --coverage          Generate code coverage report"
    echo "  -v, --verbose           Verbose output"
    echo "  -w, --wp-version VER    WordPress version (default: latest)"
    echo "  -m, --multisite         Enable multisite testing"
    echo "  -p, --php-version VER   PHP version (default: 7.4)"
    echo "  -h, --help              Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                      Run all tests"
    echo "  $0 -s unit              Run only unit tests"
    echo "  $0 -c                   Run tests with coverage"
    echo "  $0 -v -s integration    Run integration tests with verbose output"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -s|--suite)
            SUITE="$2"
            shift 2
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -w|--wp-version)
            WP_VERSION="$2"
            shift 2
            ;;
        -m|--multisite)
            WP_MULTISITE=1
            shift
            ;;
        -p|--php-version)
            PHP_VERSION="$2"
            shift 2
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Validate suite option
if [[ ! "$SUITE" =~ ^(unit|integration|performance|all)$ ]]; then
    print_error "Invalid suite: $SUITE. Must be one of: unit, integration, performance, all"
    exit 1
fi

print_status "Starting Auto Featured Image Plugin Tests"
print_status "Suite: $SUITE"
print_status "WordPress Version: $WP_VERSION"
print_status "PHP Version: $PHP_VERSION"
print_status "Multisite: $WP_MULTISITE"
print_status "Coverage: $COVERAGE"

# Check if PHPUnit is available
if ! command -v phpunit &> /dev/null; then
    print_error "PHPUnit is not installed or not in PATH"
    print_status "Please install PHPUnit: https://phpunit.de/getting-started.html"
    exit 1
fi

# Check if WordPress test environment is set up
if [[ -z "$WP_TESTS_DIR" ]]; then
    print_warning "WP_TESTS_DIR not set, using default"
    export WP_TESTS_DIR="/tmp/wordpress-tests-lib"
fi

if [[ ! -d "$WP_TESTS_DIR" ]]; then
    print_error "WordPress test environment not found at $WP_TESTS_DIR"
    print_status "Please run: bin/install-wp-tests.sh"
    exit 1
fi

# Set environment variables
export WP_TESTS_MULTISITE="$WP_MULTISITE"
export WP_CORE_DIR="/tmp/wordpress"

# Change to plugin directory
cd "$PLUGIN_DIR"

# Create coverage directory if needed
if [[ "$COVERAGE" == "true" ]]; then
    mkdir -p "$TESTS_DIR/coverage"
fi

# Build PHPUnit command
PHPUNIT_CMD="phpunit"

# Add configuration file
PHPUNIT_CMD="$PHPUNIT_CMD --configuration phpunit.xml"

# Add verbose flag if requested
if [[ "$VERBOSE" == "true" ]]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --verbose"
fi

# Add coverage flag if requested
if [[ "$COVERAGE" == "true" ]]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --coverage-html tests/coverage/html --coverage-clover tests/coverage/clover.xml"
fi

# Add specific test suite if not all
if [[ "$SUITE" != "all" ]]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --testsuite $SUITE"
fi

print_status "Running command: $PHPUNIT_CMD"

# Run the tests
if eval "$PHPUNIT_CMD"; then
    print_success "All tests passed!"
    
    if [[ "$COVERAGE" == "true" ]]; then
        print_status "Coverage report generated in tests/coverage/"
    fi
    
    exit 0
else
    print_error "Some tests failed!"
    exit 1
fi
