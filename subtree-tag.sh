# Clone all subtrees and create a tag in all of them
# used for making a release
#
# Example usage:
#
#     react/subtree-tag.sh v0.1.2

VERSION="$@"

if [ ! $VERSION ]; then
    echo "Usage: subtree-tag.sh VERSION"
    exit 1
fi

rm -rf react-tags
mkdir react-tags
cd react-tags

for subtree in event-loop stream socket http espresso; do
    git clone git@github.com:react-php/$subtree.git
    cd $subtree
    git tag $VERSION
    git push origin $VERSION
    cd ..
done
