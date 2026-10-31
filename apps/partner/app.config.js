// Extends app.json. The Firebase file holds keys, so it is never committed:
// locally put google-services.json in this folder, and on EAS add it as a
// "file" environment variable named GOOGLE_SERVICES_JSON. Without it the app
// still builds, just without push notifications.
const fs = require('fs');
const path = require('path');

module.exports = ({ config }) => {
  const file = process.env.GOOGLE_SERVICES_JSON || './google-services.json';
  if (fs.existsSync(path.resolve(__dirname, file))) {
    config.android.googleServicesFile = file;
  } else {
    delete config.android.googleServicesFile;
  }
  return config;
};
