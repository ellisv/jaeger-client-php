SHELL = /bin/sh

THRIFT_VER = 0.11.0
THRIFT_IMG = thrift:$(THRIFT_VER)
THRIFT = docker run -v "${PWD}:/data" -u $(shell id -u) -w /data $(THRIFT_IMG) thrift
THRIFT_PHP_ARGS = psr4
THRIFT_GEN_DIR = thrift-gen

.PHONY: thrift
thrift:
	rm -rf .thrift
	mkdir .thrift
	$(THRIFT) --gen php:$(THRIFT_PHP_ARGS) --out .thrift idl/thrift/jaeger.thrift
	$(THRIFT) --gen php:$(THRIFT_PHP_ARGS) --out .thrift idl/thrift/agent.thrift
	rm -rf $(THRIFT_GEN_DIR)
	mv .thrift/Jaeger/Thrift $(THRIFT_GEN_DIR)
	rm -rf .thrift
