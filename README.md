# AIO Content Clone - WordPress Plugin

## Overview

AIO Content Clone is a WordPress plugin that automatically generates Markdown "clones" of your WordPress posts for feeding to Large Language Models (LLMs). The plugin provides multiple export formats, LLM access controls, and comprehensive metadata embedding.

## Features

### Core Functionality
- **Automatic Markdown Generation**: Converts WordPress post content from HTML to Markdown on save
- **Multiple Export Formats**: Supports Markdown (.md), plain text (.txt), and JSON (.json) exports
- **Metadata Embedding**: Includes comprehensive post metadata in YAML frontmatter format
- **Manual Regeneration**: Admin interface for manually regenerating content clones
- **LLM Access Control**: User-agent based access control for LLM bots

### Advanced Features
- **LLMs.txt Manifest**: Generates robots.txt-style manifest for LLM discovery
- **Flexible Output Directory**: Configurable output directory structure
- **Multi-Post Type Support**: Works with any public post type
- **Public Download Endpoints**: Direct access URLs for LLM consumption

## Installation

1. Upload the `ai-content-clone` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings under **Settings > Content Clone**

## Configuration

### Main Settings

#### Post Types
Select which post types should have markdown clones generated:
- **Post** (default)
- **Page**
- **Custom Post Types** (any public post type)

#### LLM Bot User-Agents
List of User-Agent strings that are allowed to access the content:
```
ChatGPT
Claude-Web
GPTBot
Bingbot
```

#### Output Directory
Customize where files are stored (relative to uploads folder):
- Default: `md-clones`
- Files organized by year/month: `md-clones/2024/01/`

### Export Options

#### Allow LLM Access
- **Enabled**: Restricts access to listed user agents + logged-in editors
- **Disabled**: No access restrictions

#### Generate .txt Fallback
- Creates plain text versions alongside markdown files
- Useful for systems that don't support markdown

#### Generate .json Export
- **Enabled by default**
- Creates structured JSON export with:
  - Original HTML content
  - Converted markdown
  - Raw post content
  - Complete metadata

#### Embed Metadata
- **Enabled by default**
- Adds YAML frontmatter with configurable fields:
  - ID, slug, title, author, dates
  - Categories, tags, excerpt
  - Permalink, post type, JSON URL

### LLMs.txt Manifest

#### Generate llms.txt
- Creates a robots.txt-style manifest for LLM discovery
- Lists all user agents with access permissions

#### Manifest Filename
- Default: `llms.txt`
- Customizable filename

#### Manifest Location
- **Uploads folder**: `wp-content/uploads/llms.txt`
- **Site root**: `yoursite.com/llms.txt` (if writable)

## Usage

### Automatic Generation
Content clones are automatically generated when:
- A post is saved or updated
- Post type is enabled in settings
- User has edit permissions

### Manual Regeneration
1. Edit any post/page
2. Look for the "Markdown Clone" meta box in the sidebar
3. Click "Regenerate Markdown" button
4. Files are updated immediately

### Accessing Generated Files

#### Direct URLs
Access files using the download endpoint:
```
https://yoursite.com/?md_clone_download=123
https://yoursite.com/?md_clone_download=123&format=json
https://yoursite.com/?md_clone_download=123&format=txt
```

#### File System
Files are stored in:
```
wp-content/uploads/md-clones/YYYY/MM/
├── post-123.md
├── post-123.txt (if enabled)
└── post-123.json (if enabled)
```

## File Formats

### Markdown (.md)
```markdown
---
id: 123
title: "Sample Post"
author: "John Doe"
date: "2024-01-15 10:30:00"
categories:
  - "Technology"
  - "WordPress"
tags:
  - "plugins"
  - "markdown"
permalink: "https://yoursite.com/sample-post/"
---

# Sample Post

This is the converted markdown content with proper formatting for:

- **Bold text**
- *Italic text*
- [Links](https://example.com)
- ![Images](image.jpg)

## Subheadings

Code blocks and other elements are preserved.
```

### JSON (.json)
```json
{
  "id": 123,
  "title": "Sample Post",
  "slug": "sample-post",
  "content": {
    "html": "<p>Original HTML content...</p>",
    "markdown": "# Sample Post\n\nConverted markdown...",
    "raw": "Raw post content from database"
  },
  "meta": {
    "author": "John Doe",
    "date": "2024-01-15 10:30:00",
    "modified": "2024-01-15 11:00:00",
    "post_type": "post",
    "status": "publish",
    "permalink": "https://yoursite.com/sample-post/",
    "categories": ["Technology", "WordPress"],
    "tags": ["plugins", "markdown"],
    "excerpt": "Post excerpt..."
  }
}
```

### Plain Text (.txt)
```
Sample Post

This is the plain text version with all HTML tags removed and basic formatting preserved.
```

## Security Features

### Access Control
- User-agent validation for LLM access
- Capability checks for manual regeneration
- Sanitized input handling
- Safe file path generation

### File Security
- Files stored in uploads directory (not web root by default)
- Proper file extension validation
- Protected against path traversal attacks

## Troubleshooting

### Common Issues

#### Files Not Generated
1. Check post type is enabled in settings
2. Verify user has edit permissions
3. Ensure uploads directory is writable
4. Check error logs for PHP errors

#### LLMs.txt Not Accessible
1. Verify manifest generation is enabled
2. Check file permissions
3. For root location, ensure directory is writable
4. Test URL directly: `yoursite.com/llms.txt`

#### Download URLs Not Working
1. Check permalink structure
2. Verify post ID is correct
3. Ensure files exist in expected location
4. Check user agent restrictions

### Debug Information
Enable WordPress debug logging to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Hooks and Filters

### Actions
```php
// Triggered after markdown generation
do_action('md_clone_generated', $post_id, $files_created);

// Triggered after manifest update
do_action('md_clone_manifest_updated', $manifest_path);
```

### Filters
```php
// Modify markdown content before saving
apply_filters('md_clone_markdown_content', $content, $post);

// Modify JSON export data
apply_filters('md_clone_json_data', $data, $post);

// Modify metadata fields
apply_filters('md_clone_metadata_fields', $fields, $post);
```

## Technical Details

### Requirements
- WordPress 5.0+
- PHP 7.4+
- Write permissions to uploads directory

### Dependencies
- **Parsedown 1.8.0** (included): For HTML processing
- WordPress core functions for post handling

### Performance
- Minimal impact on post save operations
- Files generated asynchronously where possible
- Efficient file organization by date

## Changelog

### Version 1.0.0
- Initial release
- Basic markdown generation
- Multiple export formats
- LLM access control
- LLMs.txt manifest generation
- Admin interface

## License

This plugin is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0).

## Support

For support, feature requests, or bug reports:
1. Check the troubleshooting section above
2. Enable debug logging to identify issues
3. Contact the plugin author with detailed error information

## Contributing

Contributions are welcome! Please:
1. Follow WordPress coding standards
2. Include tests for new features
3. Update documentation as needed
4. Submit pull requests with clear descriptions