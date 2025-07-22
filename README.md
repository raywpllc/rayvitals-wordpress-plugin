# RayVitals WordPress Plugin

WordPress plugin for the RayVitals website audit platform. This plugin provides a comprehensive admin interface for running website audits powered by the RayVitals API.

## Features

- 🔍 **Comprehensive Website Audits**: Security, Performance, SEO, Accessibility, and UX analysis
- 🤖 **AI-Powered Insights**: Get actionable recommendations powered by Google Gemini
- 📊 **Visual Score Display**: Beautiful charts and metrics dashboard
- 🔐 **Secure API Integration**: Bearer token authentication with the RayVitals backend
- 💾 **Audit History**: Track and compare audits over time
- ⚡ **Caching Support**: Reduce API calls with intelligent result caching

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- RayVitals API key (get one from the backend deployment)

## Installation

### Via WordPress Admin

1. Download the latest release ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin
5. Go to RayVitals > Settings and add your API key

### Via Git (for development)

```bash
cd wp-content/plugins/
git clone https://github.com/raywpllc/rayvitals-wordpress-plugin.git
cd rayvitals-wordpress-plugin
npm install
npm run build
```

## Configuration

1. **Get your API Key**:
   - SSH into your RayVitals backend deployment
   - Run: `python create_initial_api_key.py`
   - Copy the generated API key

2. **Configure the Plugin**:
   - Go to WordPress Admin > RayVitals > Settings
   - Paste your API key
   - Configure caching options (optional)

## Usage

1. **Run a New Audit**:
   - Go to RayVitals > New Audit
   - Enter the URL you want to audit
   - Click "Start Audit"
   - Wait for results (usually 30-60 seconds)

2. **View Results**:
   - Overall score and category breakdowns
   - Detailed issues and recommendations
   - AI-generated summary and action items

3. **Track History**:
   - Go to RayVitals > Audit History
   - View past audits
   - Compare scores over time

## Development

### Local Setup

```bash
# Clone the repository
git clone https://github.com/raywpllc/rayvitals-wordpress-plugin.git
cd rayvitals-wordpress-plugin

# Install dependencies
npm install

# Watch for changes during development
npm run watch

# Build for production
npm run build
```

### WP Engine Deployment

1. **Configure WP Engine**:
   - Get your WP Engine site name
   - Update `WPE_INSTALL` in `deploy.sh`
   - Add WP Engine Git remote

2. **Manual Deployment**:
   ```bash
   ./deploy.sh
   ```

3. **Automated Deployment**:
   - Push to `main` branch
   - GitHub Actions will deploy automatically
   - Create a tag for releases: `git tag v1.0.1 && git push --tags`

### GitHub Secrets Required

- `WPE_SSHG_KEY_PRIVATE`: Your WP Engine SSH private key
- `WPE_ENV_NAME`: Your WP Engine environment name
- `SLACK_WEBHOOK`: (Optional) For deployment notifications

## API Endpoints Used

- `POST /api/v1/audit/start` - Start a new audit
- `GET /api/v1/audit/status/{id}` - Check audit progress
- `GET /api/v1/audit/results/{id}` - Get complete results
- `POST /api/v1/auth/validate-key` - Validate API key
- `GET /health` - Check API health

## Troubleshooting

- **API Connection Issues**: Check that your API key is valid and the backend is running
- **Timeout Errors**: Some audits may take longer; increase the timeout in settings
- **Cache Issues**: Clear the cache from Settings if you need fresh results

## License

GPL v2 or later

## Support

For issues and feature requests, please use the [GitHub issues page](https://github.com/raywpllc/rayvitals-wordpress-plugin/issues).