# RayVitals WordPress Plugin Development Plan

## Overview
Transform the plugin into a lead-generation tool with email-gated results and shortcode-based implementation.

## User Flow
1. **Home Page**: User enters website URL to audit (no email required)
2. **Processing**: API audit starts, redirect to email capture page
3. **Email Capture**: User must provide email to see results
4. **Results Page**: Display audit results in card-style format

## Shortcode Architecture

### 1. `[rayvitals_audit_form]` - Home Page Audit Form
```php
// Features:
- URL input field
- "Analyze Website" button
- Bot protection (honeypot + rate limiting)
- AJAX submission to prevent page reload
- Redirect to email capture page with audit_id parameter
```

### 2. `[rayvitals_email_capture audit_id=""]` - Email Capture
```php
// Features:
- Email input field
- "Get Your Results" button
- Progress indicator showing audit status
- Store email with audit_id in database
- Redirect to results page after email submission
```

### 3. `[rayvitals_results audit_id=""]` - Results Display
```php
// Features:
- Card-based layout (similar to Lovable mockup)
- Overall score display
- Category breakdowns (Security, Performance, SEO, etc.)
- AI-generated insights
- Call-to-action for professional services
```

## Database Schema Updates

### New Table: `wp_rayvitals_leads`
```sql
CREATE TABLE wp_rayvitals_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    UNIQUE KEY audit_email (audit_id, email),
    INDEX idx_email (email)
);
```

### Update: `wp_rayvitals_audits`
```sql
ALTER TABLE wp_rayvitals_audits 
ADD COLUMN email VARCHAR(255) AFTER url,
ADD INDEX idx_email (email);
```

## Bot Protection Strategy

### 1. Honeypot Field
- Add invisible field to form
- Reject submissions if field is filled

### 2. Rate Limiting
- Track IP addresses
- Limit to 5 audits per IP per hour
- Store in transients for auto-cleanup

### 3. Session Tracking
- Generate unique session tokens
- Verify token on submission

## Implementation Steps

### Phase 1: Core Shortcodes
1. Create shortcode handler class
2. Implement `[rayvitals_audit_form]`
3. Add AJAX handlers for form submission
4. Implement bot protection

### Phase 2: Email Capture Flow
1. Create email capture shortcode
2. Add database table for leads
3. Implement email validation
4. Create audit status checking

### Phase 3: Results Display
1. Create results shortcode
2. Implement card-based layout
3. Add Chart.js visualizations
4. Style matching Lovable mockup

### Phase 4: Admin Enhancements
1. Add leads management page
2. Export functionality for emails
3. Audit history with email data
4. Bot activity monitoring

## Security Considerations
- Sanitize all inputs
- Use nonces for forms
- Escape all outputs
- Rate limit API calls
- Validate email formats
- Prevent direct access to results without email

## Styling Approach
- Create separate CSS for public-facing shortcodes
- Use CSS Grid/Flexbox for card layouts
- Mobile-responsive design
- Match Lovable app aesthetics
- Minimal dependencies (no heavy frameworks)