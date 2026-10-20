import os
import warnings

import pytest

directory = os.path.dirname(os.path.realpath(__file__))
libs_path = os.path.join(directory, '..', 'codeformatter', 'lib')


def bundled_sources():
    sources = []
    for root, _, files in os.walk(libs_path):
        for name in files:
            if name.endswith('.py'):
                path = os.path.join(root, name)
                sources.append(os.path.relpath(path, libs_path))
    return sorted(sources)


@pytest.mark.parametrize('source', bundled_sources())
def test_bundled_lib_compiles_without_warnings(source):

    # the plugin host compiles these on load and prints every warning
    with open(os.path.join(libs_path, source), 'rb') as f:
        code = f.read()

    with warnings.catch_warnings(record=True) as caught:
        warnings.simplefilter('always')
        compile(code, source, 'exec', dont_inherit=True)

    assert ['%s: %s' % (w.lineno, w.message) for w in caught] == []
