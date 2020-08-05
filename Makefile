clean:
	rm -rf build

build:
	mkdir build
	ppm --no-intro --compile="src/BackgroundWorker" --directory="build"

install:
	ppm --no-intro --no-prompt --install="build/net.intellivoid.background_background.ppm"