
test: sandbox/bin/pythontidy
	sandbox/bin/pythontidy setup.py

sandbox/bin/pythontidy: dist/PythonTidy-1.21.zip sandbox/bin/easy_install
	sandbox/bin/easy_install dist/PythonTidy-1.21.zip

dist/PythonTidy-1.21.zip:
	python setup.py egg_info -RDb '' sdist --formats=zip

sandbox/bin/easy_install:
	virtualenv --no-site-packages --distribute sandbox

clean:
	rm -rf dist/ sandbox/

.PHONY: test clean
