# Requirements Document

## Introduction

The Auto Featured Image plugin is a high-performance WordPress solution designed to automatically assign featured images to posts that lack them. The plugin is specifically engineered for websites with very large datasets (tens of thousands to millions of posts and images), utilizing asynchronous background processing to prevent server timeouts and maintain optimal site performance. The system uses a job queue architecture with Action Scheduler to handle batch processing while providing real-time progress monitoring through an intuitive admin interface.

## Requirements

### Requirement 1: Scalable Content Scanning

**User Story:** As a website administrator with a large content database, I want to efficiently scan all my posts to identify which ones are missing featured images, so that I can process them without causing server performance issues.

#### Acceptance Criteria

1. WHEN the user initiates a scan THEN the system SHALL process posts in configurable batches (500-1000 posts per batch) to prevent memory exhaustion
2. WHEN scanning posts THEN the system SHALL use WP_Query with pagination to avoid loading all posts into memory simultaneously
3. WHEN a post is found without a _thumbnail_id meta key THEN the system SHALL record its ID in the wp_afi_job_items table
4. WHEN the scan is running THEN the system SHALL provide real-time progress updates via AJAX polling without blocking the admin interface
5. WHEN the scan completes THEN the system SHALL display the total count of posts found that need featured images

### Requirement 2: Asynchronous Background Processing

**User Story:** As a website administrator, I want all processing to happen in the background using a reliable job queue system, so that my website remains fast and responsive for visitors during the entire operation.

#### Acceptance Criteria

1. WHEN any processing task is initiated THEN the system SHALL use Action Scheduler library for all background operations
2. WHEN a job is running THEN the system SHALL NOT depend on user browser sessions or WP-Cron for execution
3. WHEN processing posts THEN the system SHALL handle each post assignment as a separate queued task to prevent PHP timeouts
4. WHEN the server experiences high load THEN the system SHALL continue processing jobs reliably without manual intervention
5. WHEN a job is paused THEN the system SHALL allow resuming from the exact point where it was stopped

### Requirement 3: High-Performance Image Assignment

**User Story:** As a website administrator with a large media library, I want the system to randomly assign images to posts efficiently, so that the process doesn't slow down due to database performance issues.

#### Acceptance Criteria

1. WHEN selecting random images THEN the system SHALL NOT use ORDER BY RAND() queries on the wp_posts table
2. WHEN assigning images THEN the system SHALL use the count-and-offset method to select random images efficiently
3. WHEN processing multiple posts THEN the system SHALL optionally batch-fetch 50-100 random image IDs to reduce database queries
4. WHEN image filters are applied THEN the system SHALL only select from images matching the specified criteria (date range, keywords)
5. WHEN an image is assigned THEN the system SHALL update the post's _thumbnail_id meta field and log the assignment

### Requirement 4: Interactive Job Management Interface

**User Story:** As a website administrator, I want a comprehensive admin interface to create, monitor, and control processing jobs, so that I have full visibility and control over the automated process.

#### Acceptance Criteria

1. WHEN accessing the plugin THEN the system SHALL provide an admin page at Tools -> Auto Featured Image
2. WHEN viewing the dashboard THEN the system SHALL display a history of previous jobs with status, progress, and dates
3. WHEN creating a new job THEN the system SHALL allow selection of post types and optional image source filters
4. WHEN a job is running THEN the system SHALL display real-time progress with dynamic progress bars and counters
5. WHEN managing jobs THEN the system SHALL provide controls to start, pause, resume, and cancel processing jobs
6. WHEN reviewing scan results THEN the system SHALL display found posts in a paginated, searchable table to handle large datasets

### Requirement 5: Scalable Database Architecture

**User Story:** As a website administrator with large datasets, I want the plugin to use optimized database storage, so that it doesn't impact my site's performance or bloat my database with inefficient data structures.

#### Acceptance Criteria

1. WHEN the plugin is activated THEN the system SHALL create wp_afi_jobs and wp_afi_job_items custom tables
2. WHEN storing job data THEN the system SHALL NOT use wp_options for bulk data storage
3. WHEN creating database tables THEN the system SHALL include proper indexing on foreign keys and lookup columns
4. WHEN storing job items THEN the system SHALL use the wp_afi_job_items table with fields for job_id, post_id, status, assigned_image_id, and log_message
5. WHEN querying job data THEN the system SHALL use efficient database queries optimized for large datasets

### Requirement 6: Comprehensive Logging and Monitoring

**User Story:** As a website administrator, I want detailed logging of all plugin activities, so that I can audit the process, debug issues, and monitor system performance.

#### Acceptance Criteria

1. WHEN any processing action occurs THEN the system SHALL log the action with timestamp and relevant details
2. WHEN viewing job progress THEN the system SHALL display a live log view showing the latest updates
3. WHEN an error occurs THEN the system SHALL log the error details and continue processing other items
4. WHEN logs accumulate over time THEN the system SHALL provide automatic cleanup of old log data based on configurable retention periods
5. WHEN debugging issues THEN the system SHALL provide detailed error messages and stack traces in the logs

### Requirement 7: Data Management and Cleanup

**User Story:** As a website administrator, I want control over data retention and cleanup, so that the plugin doesn't bloat my database over time and I can cleanly remove it if needed.

#### Acceptance Criteria

1. WHEN configuring the plugin THEN the system SHALL provide settings to automatically purge old job and log data (e.g., delete data older than 90 days)
2. WHEN uninstalling the plugin THEN the system SHALL provide a clear option to delete all associated custom tables and data
3. WHEN data cleanup runs THEN the system SHALL remove old records from both wp_afi_jobs and wp_afi_job_items tables
4. WHEN the plugin is deactivated THEN the system SHALL NOT automatically delete user data without explicit permission
5. WHEN cleaning up data THEN the system SHALL maintain referential integrity between jobs and job items tables

### Requirement 8: Security and WordPress Standards Compliance

**User Story:** As a website administrator, I want the plugin to follow WordPress security best practices and coding standards, so that it doesn't introduce vulnerabilities or conflicts with my site.

#### Acceptance Criteria

1. WHEN any admin action is performed THEN the system SHALL verify user capabilities using current_user_can()
2. WHEN processing user input THEN the system SHALL sanitize all input data using WordPress sanitization functions
3. WHEN displaying output THEN the system SHALL escape all output using WordPress escaping functions
4. WHEN performing admin actions THEN the system SHALL use nonces for CSRF protection
5. WHEN naming functions and classes THEN the system SHALL use the 'afi_' prefix to prevent conflicts
6. WHEN following coding standards THEN the system SHALL adhere to WordPress Coding Standards and use Object-Oriented Programming structure

### Requirement 9: Advanced Image Filtering and Selection

**User Story:** As a website administrator with a large media library, I want advanced filtering options for image selection, so that I can control which images are used for automatic assignment based on my content strategy.

#### Acceptance Criteria

1. WHEN configuring a job THEN the system SHALL allow filtering images by upload date range
2. WHEN setting image filters THEN the system SHALL support keyword filtering by filename, title, or alt text
3. WHEN no filters are applied THEN the system SHALL use all available images in the media library
4. WHEN filters are applied THEN the system SHALL cache the filtered image count for performance optimization
5. WHEN selecting random images THEN the system SHALL respect all active filters while maintaining random distribution

### Requirement 10: Real-time Progress Monitoring and Control

**User Story:** As a website administrator, I want real-time visibility into job progress with the ability to control running jobs, so that I can monitor the process and intervene if necessary without losing progress.

#### Acceptance Criteria

1. WHEN a job is running THEN the system SHALL update progress indicators every 5-10 seconds via AJAX polling
2. WHEN displaying progress THEN the system SHALL show processed count, total count, and percentage completion
3. WHEN a job is paused THEN the system SHALL immediately stop processing new items while preserving current progress
4. WHEN resuming a paused job THEN the system SHALL continue from the exact point where it was paused
5. WHEN canceling a job THEN the system SHALL stop all processing and mark the job as canceled while preserving completed assignments