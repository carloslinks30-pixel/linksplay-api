services:
  - type: web
    name: linksplay-api
    runtime: php
    plan: free
    buildCommand: ""
    startCommand: php -S 0.0.0.0:$PORT index.php
