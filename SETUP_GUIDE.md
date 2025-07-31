# AIO Content Clone - Quick Setup Guide

## Installation Steps

### 1. Upload Plugin
```bash
# Upload the ai-content-clone folder to:
wp-content/plugins/ai-content-clone/
```

### 2. Activate Plugin
- Go to **WordPress Admin > Plugins**
- Find "AIO Content Clone"
- Click **Activate**

### 3. Configure Settings
- Navigate to **Settings > Content Clone**
- Configure your preferences:

#### Essential Settings
```
✅ Post Types: Select "Post" (and others as needed)
✅ LLM Bot User-Agents: Add your LLM bots
    ChatGPT
    Claude-Web
    GPTBot
    
✅ Output Directory: md-clones (default is fine)
✅ Generate .json Export: Enabled
✅ Embed Metadata: Enabled
```

#### Optional Settings
```
⚪ Allow LLM Access: Enable if you want user-agent restrictions
⚪ Generate .txt Fallback: Enable for plain text versions
⚪ Generate llms.txt: Enable for LLM discovery
```

### 4. Test Installation

#### Create Test Post
1. Go to **Posts > Add New**
2. Create a test post with:
   - Title: "Test Post for AIO Content Clone"
   - Content: Some HTML content with **bold**, *italic*, and [links](https://example.com)
3. **Publish** the post

#### Verify File Generation
1. Check your uploads directory:
   ```
   wp-content/uploads/md-clones/2024/01/
   ├── post-123.md
   └── post-123.json
   ```

2. Test the download URL:
   ```
   https://yoursite.com/?md_clone_download=123
   ```

#### Test Manual Regeneration
1. Edit the test post
2. Look for "Markdown Clone" meta box in sidebar
3. Click "Regenerate Markdown"
4. Should see success message

## Quick Configuration Examples

### For ChatGPT Access
```
Settings > Content Clone:
✅ Allow LLM Access: Yes
✅ LLM Bot User-Agents:
    ChatGPT
    GPTBot
```

### For Public Access
```
Settings > Content Clone:
⚪ Allow LLM Access: No
(Anyone can access the files)
```

### For Maximum Compatibility
```
Settings > Content Clone:
✅ Generate .txt Fallback: Yes
✅ Generate .json Export: Yes
✅ Embed Metadata: Yes
```

## Troubleshooting

### Files Not Created?
1. Check WordPress error log
2. Verify uploads directory permissions
3. Ensure post type is enabled in settings

### Can't Access Downloads?
1. Test URL directly: `yoursite.com/?md_clone_download=POST_ID`
2. Check user-agent restrictions if enabled
3. Verify file exists in uploads directory

### LLMs.txt Not Working?
1. Enable "Generate llms.txt" in settings
2. Check file permissions
3. Try "Uploads folder" location first

## File Locations

### Generated Files
```
wp-content/uploads/md-clones/YYYY/MM/
├── post-123.md      # Markdown with metadata
├── post-123.json    # Structured data
└── post-123.txt     # Plain text (if enabled)
```

### Manifest File
```
wp-content/uploads/llms.txt     # Default location
# OR
yoursite.com/llms.txt          # Root location (if enabled)
```

## Next Steps

1. **Test with Real Content**: Create posts with various content types
2. **Configure LLM Access**: Set up proper user-agent restrictions
3. **Monitor File Generation**: Check that files are created on post save
4. **Test Download URLs**: Verify LLMs can access the content
5. **Set Up Monitoring**: Watch for any errors in WordPress logs

## Support

If you encounter issues:
1. Enable WordPress debug logging
2. Check the TEST_REPORT.md for known issues
3. Review the full README.md for detailed documentation

---

**Quick Start Complete!** Your plugin should now be generating markdown clones of your WordPress content.