#!/bin/bash
set -e

echo "🧪 ShopSavvy WordPress Plugin Tests"
echo "====================================="

if [ "$1" = "--integration" ]; then
  if [ -z "$SHOPSAVVY_API_KEY" ]; then
    echo "❌ Set SHOPSAVVY_API_KEY env var to run integration tests"
    echo "   Get a key at https://shopsavvy.com/data"
    exit 1
  fi
  echo "Running integration tests (live API)..."
  echo ""
  echo "Testing API connectivity..."
  RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" \
    -H "Authorization: Bearer $SHOPSAVVY_API_KEY" \
    -H "User-Agent: ShopSavvy-WordPress-Test/1.0" \
    "https://api.shopsavvy.com/v1/usage")
  if [ "$RESPONSE" = "200" ]; then
    echo "  ✅ API key valid"
  else
    echo "  ❌ API returned HTTP $RESPONSE"
    exit 1
  fi

  echo "Testing product search..."
  SEARCH=$(curl -s \
    -H "Authorization: Bearer $SHOPSAVVY_API_KEY" \
    -H "User-Agent: ShopSavvy-WordPress-Test/1.0" \
    "https://api.shopsavvy.com/v1/products/search?q=airpods+pro&limit=1")
  if echo "$SEARCH" | grep -q '"success":true'; then
    echo "  ✅ Product search works"
  else
    echo "  ❌ Product search failed"
    echo "  $SEARCH"
    exit 1
  fi

  echo "Testing product offers..."
  OFFERS=$(curl -s \
    -H "Authorization: Bearer $SHOPSAVVY_API_KEY" \
    -H "User-Agent: ShopSavvy-WordPress-Test/1.0" \
    "https://api.shopsavvy.com/v1/products/offers?ids=B0BSHF7WHW")
  if echo "$OFFERS" | grep -q '"success":true'; then
    echo "  ✅ Product offers works"
  else
    echo "  ❌ Product offers failed"
    exit 1
  fi

  echo "Testing price history..."
  START=$(date -v-30d +%Y-%m-%d 2>/dev/null || date -d '30 days ago' +%Y-%m-%d)
  END=$(date +%Y-%m-%d)
  HISTORY=$(curl -s \
    -H "Authorization: Bearer $SHOPSAVVY_API_KEY" \
    -H "User-Agent: ShopSavvy-WordPress-Test/1.0" \
    "https://api.shopsavvy.com/v1/products/offers/history?ids=B0BSHF7WHW&start=$START&end=$END")
  if echo "$HISTORY" | grep -q '"success":true'; then
    echo "  ✅ Price history works"
  else
    echo "  ❌ Price history failed"
    exit 1
  fi

  echo ""
  echo "✅ All integration tests passed"
else
  echo "Running syntax checks..."
  echo ""

  if command -v php &> /dev/null; then
    ERRORS=0
    for f in $(find . -name "*.php" -not -path "./vendor/*"); do
      if ! php -l "$f" > /dev/null 2>&1; then
        echo "  ❌ Syntax error: $f"
        ERRORS=$((ERRORS + 1))
      fi
    done
    if [ $ERRORS -eq 0 ]; then
      echo "  ✅ All PHP files pass syntax check"
    else
      echo "  ❌ $ERRORS files have syntax errors"
      exit 1
    fi
  else
    echo "  ⚠️  PHP not installed — skipping syntax check"
  fi

  echo "Checking file structure..."
  REQUIRED_FILES="wordpress-shopsavvy.php includes/class-shopsavvy-client.php includes/class-shopsavvy-settings.php includes/class-shopsavvy-shortcode.php includes/class-shopsavvy-widget.php includes/class-shopsavvy-rest.php includes/class-shopsavvy-cache.php blocks/price-comparison/block.json"
  MISSING=0
  for f in $REQUIRED_FILES; do
    if [ ! -f "$f" ]; then
      echo "  ❌ Missing: $f"
      MISSING=$((MISSING + 1))
    fi
  done
  if [ $MISSING -eq 0 ]; then
    echo "  ✅ All required files present"
  else
    echo "  ❌ $MISSING required files missing"
    exit 1
  fi

  echo "Checking Gutenberg block.json..."
  if python3 -c "import json; json.load(open('blocks/price-comparison/block.json'))" 2>/dev/null; then
    echo "  ✅ block.json is valid JSON"
  else
    echo "  ❌ block.json is invalid"
    exit 1
  fi

  echo ""
  echo "✅ All unit tests passed"
fi
