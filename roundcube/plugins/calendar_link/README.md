# Calendar Link Plugin for Roundcube

This plugin adds a "Calendar" link to Roundcube's navigation that opens your calendar app in a new tab.

## Installation

1. **Copy the plugin** to `roundcube/plugins/calendar_link/`
2. **Enable the plugin** in Roundcube settings
3. **Configure the calendar URL** in the config file

## Configuration

Edit `config.inc.php` to set:
- `calendar_app_url`: URL to your calendar app (default: http://localhost:4200)
- `calendar_link_text`: Text for the calendar link
- `calendar_open_new_tab`: Whether to open in new tab

## Features

- ✅ **Simple integration** - just adds a link
- ✅ **Easy to customize** - change URL and text
- ✅ **Lightweight** - minimal code changes
- ✅ **User-friendly** - clear calendar access

## Usage

1. **Users see "📅 Calendar" link** in Roundcube navigation
2. **Click the link** to open your calendar app
3. **Calendar opens in new tab** - no disruption to email

## Customization

You can modify:
- Link appearance (CSS)
- Link position in navigation
- Calendar app URL
- Link text and icon

## Troubleshooting

- **Link not visible**: Check if plugin is enabled
- **Wrong URL**: Update `calendar_app_url` in config
- **Styling issues**: Check CSS classes in the plugin
