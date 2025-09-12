# Firefly III Plugin Architecture

## Overview

This document describes the plugin architecture implemented for Firefly III, specifically for the PFinance automation plugin. The architecture allows Firefly III to work with or without plugins, and enables independent development and deployment of plugin functionality.

## Architecture Components

### 1. Plugin System Structure

```
firefly-iii/
├── plugins/
│   ├── PluginManager.php          # Core plugin management class
│   ├── manage.php                 # CLI tool for plugin management
│   └── automation/
│       └── plugin.json            # Plugin configuration
└── resources/views/accounts/show.twig  # Modified to support plugins

pfinance/
└── pfinance-microservice/
    ├── static/
    │   ├── js/automation.js       # Plugin JavaScript
    │   ├── css/automation.css     # Plugin styles
    │   └── templates/
    │       └── automation-section.html  # Plugin UI template
    └── app/__init__.py            # Flask app with static file serving
```

### 2. Plugin Configuration

Each plugin has a `plugin.json` configuration file:

```json
{
  "name": "pfinance-automation",
  "version": "1.0.0",
  "description": "PFinance automation plugin for Firefly III",
  "author": "Cameron",
  "enabled": true,
  "dependencies": {
    "pfinance-microservice": "http://localhost:5001"
  },
  "assets": {
    "css": ["http://localhost:5001/static/css/automation.css"],
    "js": ["http://localhost:5001/static/js/automation.js"]
  },
  "templates": {
    "accounts_show": "http://localhost:5001/static/templates/automation-section.html"
  }
}
```

### 3. Plugin Loading Mechanism

The plugin system works as follows:

1. **Firefly III loads** and checks for enabled plugins
2. **Plugin assets are loaded** dynamically (CSS, JS, templates)
3. **Plugin UI is injected** into designated areas of the page
4. **Plugin functionality** becomes available to users

## Usage

### Installing Firefly III Without Plugins

Firefly III can be installed and used normally without any plugins. The automation functionality will simply not be available.

### Adding the Automation Plugin

1. **Ensure pfinance microservice is running** on `http://localhost:5001`
2. **Plugin is automatically detected** and loaded when the microservice is available
3. **Automation section appears** in account pages when the plugin loads successfully

### Plugin Management

Use the CLI tool to manage plugins:

```bash
# Enable a plugin
php plugins/manage.php enable pfinance-automation

# Disable a plugin
php plugins/manage.php disable pfinance-automation

# Check plugin status
php plugins/manage.php status

# List all plugins
php plugins/manage.php list
```

## Benefits

### 1. Separation of Concerns
- **Firefly III**: Core financial management functionality
- **PFinance Plugin**: Automation and external service integration
- **Clear boundaries** between core and plugin functionality

### 2. Independent Development
- **Plugin development** can happen independently of Firefly III
- **Version control** is maintained in separate repositories
- **Deployment** can be done independently

### 3. Optional Functionality
- **Firefly III works** without any plugins
- **Plugins are optional** and can be added as needed
- **Graceful degradation** when plugins are unavailable

### 4. Maintainability
- **Plugin code** is organized in logical locations
- **Clear interfaces** between core and plugin systems
- **Easy to add/remove** plugins

## Technical Implementation

### Plugin Loading Process

1. **Template renders** with plugin placeholder: `<div id="plugin-automation-section"></div>`
2. **JavaScript checks** if plugin microservice is available
3. **Assets are loaded** dynamically (CSS, JS)
4. **Template is fetched** from microservice
5. **UI is injected** into the placeholder
6. **Plugin functionality** becomes active

### Error Handling

- **Microservice unavailable**: Plugin gracefully fails to load
- **Network errors**: Plugin loading is skipped
- **Template errors**: Fallback to core functionality
- **JavaScript errors**: Isolated to plugin scope

### Security Considerations

- **CSP compliance**: All plugin assets respect Content Security Policy
- **CORS handling**: Microservice properly configured for cross-origin requests
- **Input validation**: Plugin templates are processed safely
- **Isolation**: Plugin failures don't affect core functionality

## Development Workflow

### For Plugin Development

1. **Develop in pfinance repo**: All plugin code lives here
2. **Test with microservice**: Run pfinance microservice locally
3. **Update plugin assets**: CSS, JS, templates in microservice
4. **Deploy microservice**: Plugin updates are deployed with microservice

### For Firefly III Development

1. **Core functionality**: Develop without plugin dependencies
2. **Plugin integration**: Use plugin system for optional features
3. **Backward compatibility**: Ensure core works without plugins

## Future Enhancements

### Potential Improvements

1. **Plugin registry**: Centralized plugin discovery and management
2. **Plugin dependencies**: Automatic dependency resolution
3. **Plugin versioning**: Support for multiple plugin versions
4. **Plugin configuration**: UI for plugin settings
5. **Plugin marketplace**: Community plugin sharing

### Extension Points

1. **Additional templates**: Support for more UI injection points
2. **API integration**: Plugin-specific API endpoints
3. **Database integration**: Plugin-specific data storage
4. **Event system**: Plugin-to-plugin communication
5. **Authentication**: Plugin-specific user permissions

## Conclusion

The plugin architecture provides a clean, maintainable way to extend Firefly III with optional functionality while keeping the core application focused and lightweight. The PFinance automation plugin demonstrates how external services can be integrated seamlessly while maintaining proper separation of concerns.
