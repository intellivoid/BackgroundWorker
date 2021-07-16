clean:
	rm -rf build

build:
	mkdir build
	ppm --no-intro --compile="src/BackgroundWorker" --directory="build"

update:
	ppm --generate-package="src/BackgroundWorker"

install:
	ppm --no-intro --no-prompt --fix-conflict --install="build/net.intellivoid.background_worker.ppm"

install_fast:
	ppm --no-intro --no-prompt --fix-conflict --skip-dependencies --install="build/net.intellivoid.background_worker.ppm"