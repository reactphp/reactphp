git clone https://github.com/react-php/react react-split
cd react-split
git checkout master
git pull origin master

git branch -D event-loop
git subtree split --prefix=src/React/EventLoop/ -b event-loop
git remote add event-loop git@github.com:react-php/event-loop.git
git push -f event-loop event-loop:master

git branch -D socket
git subtree split --prefix=src/React/Socket/ -b socket
git remote add socket git@github.com:react-php/socket.git
git push -f socket socket:master

git branch -D http
git subtree split --prefix=src/React/Http/ -b http
git remote add http git@github.com:react-php/http.git
git push -f http http:master

git branch -D espresso
git subtree split --prefix=src/React/Espresso/ -b espresso
git remote add espresso git@github.com:react-php/espresso.git
git push -f espresso espresso:master
