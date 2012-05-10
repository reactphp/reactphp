git clone https://github.com/react-php/react react-split
cd react-split

git-subtree split --prefix=src/React/EventLoop/ -b event-loop
git remote add event-loop git@github.com:react-php/event-loop.git
git push event-loop event-loop:master

git-subtree split --prefix=src/React/Socket/ -b socket
git remote add socket git@github.com:react-php/socket.git
git push socket socket:master

git-subtree split --prefix=src/React/Http/ -b http
git remote add http git@github.com:react-php/http.git
git push http http:master

git-subtree split --prefix=src/React/Espresso/ -b espresso
git remote add espresso git@github.com:react-php/espresso.git
git push espresso espresso:master
