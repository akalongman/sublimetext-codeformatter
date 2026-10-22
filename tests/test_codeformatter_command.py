import importlib
import importlib.util
import os
import sys
import types

import pytest
from unittest.mock import ANY, Mock

import sublime


REPO_ROOT = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
PACKAGE = 'CodeFormatter'


@pytest.fixture
def plugin(monkeypatch, request):
    """Import CodeFormatter.py the way Sublime Text does, as a submodule of
    the CodeFormatter package, and drop every module the import created
    afterwards so that sys.modules ends as it started."""
    package = types.ModuleType(PACKAGE)
    package.__path__ = [REPO_ROOT]
    monkeypatch.setitem(sys.modules, PACKAGE, package)

    # the plugin subclasses these, and a Mock cannot be a base class
    sublime_plugin = types.ModuleType('sublime_plugin')
    sublime_plugin.TextCommand = object
    sublime_plugin.EventListener = object
    monkeypatch.setitem(sys.modules, 'sublime_plugin', sublime_plugin)

    # Python 3.12 removed imp, which the plugin imports under Sublime Text 3
    if importlib.util.find_spec('imp') is None:
        imp = types.ModuleType('imp')
        imp.reload = importlib.reload
        monkeypatch.setitem(sys.modules, 'imp', imp)

    known = set(sys.modules)

    def restore_shared_state():
        for name in set(sys.modules) - known:
            if name.startswith(PACKAGE + '.'):
                del sys.modules[name]
        # the sublime mock is shared with the other test files, which
        # assert on its call history
        sublime.reset_mock()

    request.addfinalizer(restore_shared_state)

    module = importlib.import_module(PACKAGE + '.CodeFormatter')
    sublime.reset_mock()
    return module


def fake_formatter(result):
    class FakeFormatter(object):

        def __init__(self, view, syntax=None):
            pass

        def exists(self):
            return True

        def format_on_save_enabled(self):
            return True

        def format(self, text):
            return result

    return FakeFormatter


def make_view():
    view = Mock()
    view.is_scratch.return_value = False
    view.size.return_value = 3
    view.substr.return_value = 'x=1'
    return view


def test_saving_error_goes_to_status_bar(plugin, monkeypatch, capsys):
    monkeypatch.setattr(
        plugin, 'Formatter', fake_formatter(('', 'boom\nsecond line')))

    plugin.run_formatter(make_view(), Mock(), saving=True)

    sublime.error_message.assert_not_called()
    sublime.status_message.assert_called_once_with(
        'CodeFormatter: Format error: boom')
    assert 'boom\nsecond line' in capsys.readouterr().out


def test_format_code_error_shows_dialog(plugin, monkeypatch):
    monkeypatch.setattr(
        plugin, 'Formatter', fake_formatter(('', 'boom\nsecond line')))

    plugin.run_formatter(make_view(), Mock())

    sublime.error_message.assert_called_once_with(
        'CodeFormatter\n\nFormat error:\nboom\nsecond line')
    sublime.status_message.assert_not_called()


def test_saving_success_replaces_buffer(plugin, monkeypatch):
    monkeypatch.setattr(plugin, 'Formatter', fake_formatter(('x = 1', '')))
    view = make_view()
    edit = Mock()

    plugin.run_formatter(view, edit, saving=True)

    view.replace.assert_called_once_with(edit, ANY, 'x = 1')
    sublime.error_message.assert_not_called()
    sublime.status_message.assert_not_called()
