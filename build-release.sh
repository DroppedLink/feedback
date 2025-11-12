#!/bin/bash
# WordPress Plugin Release Builder
# Creates properly structured ZIP files for WordPress plugin installation

# Configuration
PLUGIN_SLUG="user-feedback"
BUILD_DIR="build"
RELEASE_DIR="releases"

# Get version from plugin file or use parameter
if [ -n "$1" ]; then
    VERSION="$1"
else
    VERSION=$(grep "Version:" user-feedback.php | awk '{print $3}')
fi

echo "🔨 Building ${PLUGIN_SLUG} v${VERSION}..."

# Clean previous builds
rm -rf ${BUILD_DIR}
rm -rf ${RELEASE_DIR}
mkdir -p ${BUILD_DIR}
mkdir -p ${RELEASE_DIR}

# Copy plugin files (excluding dev files)
echo "📦 Copying files..."
rsync -av \
    --exclude='.git*' \
    --exclude='node_modules' \
    --exclude='build' \
    --exclude='releases' \
    --exclude='*.sh' \
    --exclude='.DS_Store' \
    --exclude='.github' \
    --exclude='WARP.md' \
    --exclude='*_SUMMARY.md' \
    --exclude='*_GUIDE.md' \
    --exclude='*_ROADMAP.md' \
    . ${BUILD_DIR}/${PLUGIN_SLUG}/ > /dev/null

# Create ZIP with correct structure
echo "🗜️  Creating ZIP..."
cd ${BUILD_DIR}
zip -r -q ../${RELEASE_DIR}/${PLUGIN_SLUG}.${VERSION}.zip ${PLUGIN_SLUG}/
cd ..

# Get file size
SIZE=$(du -h ${RELEASE_DIR}/${PLUGIN_SLUG}.${VERSION}.zip | cut -f1)

echo ""
echo "✅ Created: ${RELEASE_DIR}/${PLUGIN_SLUG}.${VERSION}.zip (${SIZE})"
echo "📦 Extracts to: ${PLUGIN_SLUG}/ (correct for WordPress!)"
echo ""
echo "📤 To upload to GitHub release:"
echo "   gh release upload v${VERSION} ${RELEASE_DIR}/${PLUGIN_SLUG}.${VERSION}.zip --repo DroppedLink/user-feedback"
echo ""

