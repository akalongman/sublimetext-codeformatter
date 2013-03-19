#!/usr/bin/python
# -*- coding: utf-8 -*-

# TestSuite.py
# 2009 Oct 13 . ccr

"""Exercise PythonTidy over the Python Test Suite.

"""

from __future__ import division
import os
import subprocess
import re
import tarfile
import PythonTidy

ZERO = 0
SPACE = ' '
NULL = ''
NUL = '\x00'
NA = -1

PATH_ARCHIVE = '/var/cache/apt/archives/Python-2.5.2.tar.bz2'
PATTERN_MEMBER = 'Python-2.5.2/Lib/test/'
PATH_TARGET = '/home/crhode/lab'
PATH_TESTS = os.path.join(PATH_TARGET, PATTERN_MEMBER[:-1])
PATH_TEST_PACKAGE = os.path.split(PATH_TESTS)[ZERO]
PATH_REGRTEST_STDOUT = '/tmp/regrtest.txt'
PAT_PASS_FAIL = re.compile(r'^\d+\s+tests?\s+((?:failed)|(?:skipped)):\s+(.*)$', re.MULTILINE)
PAT_STATE = re.compile(r'^\s*(?:#\s*([^:]*):)?\s*(.*)\s*$')

SENTINEL = {
    'run': None,
    'u_skip': 'User skipped',        
    'a_skip': 'Failed after tidying',
    'b_skip': 'Failed before tidying',
    'r_fail': 'Regression test failed', 
    'r_skip': 'Regression test skipped',
    }

STATE = dict(
    (value, key)
    for (key, value) in SENTINEL.iteritems()
    )

class Archive(object):

    def __init__(self, path):
        self.name = path
        self.tar = tarfile.open(self.name)
        return

    def get_members(self):
        return self.tar.getmembers()

    def extract(self, pattern, path):
        targets = [
            member
            for member in self.get_members()
            if member.name.startswith(pattern)
            ]
        return self.tar.extractall(path, targets)


class Test(object):

    def __init__(self, name, state='run'):
        self.name = name
        self.set_state(state)
        return

    def __str__(self):
        sentinel = SENTINEL[self.state]
        if sentinel is None:
            result = '  %s' % self.name
        else:
            result = '# %s:  %s' % (sentinel, self.name)
        return result

    def set_state(self, state):
        self.state = state
        return self

    def is_run(self):
        return self.state in ['run']


def get_test(line):
    match = PAT_STATE.match(line)
    (sentinel, name) = match.group(1,2)
    if sentinel is None:
        if line.startswith('#'):
            result = Test(name=name, state='u_skip')
        else:
            result = Test(name=name, state='run')
    else:
        sentinel = sentinel.capitalize()
        result = Test(name=name, state=STATE[sentinel])
    return result


class Repertoire(list):

    def __init__(self):
        list.__init__(self)
        self.file_name = os.path.join(PATH_TESTS, 'repertoire.dat')
        return

    def get_from_dir(self):
        for test in os.listdir(PATH_TESTS):
            if test.startswith('test_') and test.endswith('.py'):
                test = Test(test[:-3])
                self.append(test)
        return self

    def load(self):
        del self[:]
        flob = open(self.file_name, 'r')
        for line in flob:
            test = get_test(line[:-1])
            self.append(test)
        flob.close()
        return self

    def save(self):
        flob = open(self.file_name, 'w')
        for test in self:
            flob.write('%s\n' % str(test))
        flob.close()
        return self

    def spike(self):

        """On my system these tests fail before tidying:

        """
        
        for test in self:
            if test.name in [
                'test_imageop',
                'test_pyclbr',
                'test_sys',
                ]:
                test.set_state('b_skip')
        return self

    def omit(self):

        """These tests fail after tidying:
        
        """

        for test in self:
            if test.name in [
                'test_grammar',

                                # *test_grammar* exposes bug 6978 in
                                # the *compiler* module.  Tuples are
                                # immutable and hashable and thus
                                # suitable as dict indices.  Whereas a
                                # singleton tuple literal (x,) is
                                # valid as an index, the *compiler*
                                # module parses it as x when it
                                # appears.

                'test_dis',

                                # *test_dis* compares "disassembled"
                                # Python byte code to what is
                                # expected.  While byte code for a
                                # tidied script should be functionally
                                # equivalent to the untidied version,
                                # it will not necessarily be
                                # identical.
                
                'test_trace',

                                # *test_trace* compares the line
                                # numbers in a functional trace of a
                                # running script with those expected.
                                # A statement in a tidied script will
                                # generally have a line number
                                # slightly different from the same
                                # statement in the untidied version.

                'test_doctest',

                                # *test_doctest* is an extensive suite
                                # of tests of the *doctest* module,
                                # which itself is used to document
                                # test code within docstrings and at
                                # need to compare instant results
                                # against those expected.  One of the
                                # tests in *test_doctest* appears to
                                # require line numbers consistent with
                                # expectations, but tidied scripts
                                # generally violate such conditions as
                                # explained above.
                
                ]:
                test.set_state('a_skip')
        return self

    def log_results(self):
        flob = open(PATH_REGRTEST_STDOUT, 'r')
        _buffer = flob.read()
        flob.close()
        _buffer = _buffer.replace('\n    ', SPACE)
        result = PAT_PASS_FAIL.split(_buffer)
        while result:
            sentinel = result.pop(ZERO)
            if sentinel in ['failed', 'skipped']:
                if result:
                    tests = result.pop(ZERO)
                    tests = tests.split()
                    for test in tests:
                        self.log_result(sentinel, test)
        return self

    def log_result(self, sentinel, target):
        for test in self:
            if test.name == target:
                if sentinel in ['failed']:
                    test.set_state('r_fail')
                elif sentinel in ['skipped']:
                    test.set_state('r_skip')
                break
        return self

    def tidy_all(self):
        for test in self:
            if test.is_run():
                self.tidy(test.name)
        return self

    def tidy(self, name):
        test_name = os.path.join(PATH_TESTS, '%s.py' % name)
        back_name = os.path.join(PATH_TESTS, '%s.bak' % name)
        if os.path.exists(back_name):
            pass
        else:
            os.rename(test_name, back_name)
            print 'Converting', name
            PythonTidy.tidy_up(file_in=back_name, file_out=test_name)
        return self

    def untidy_all(self):
        for test in self:
            self.untidy(test.name)
        return self
        
    def untidy(self, name):
        test_name = os.path.join(PATH_TESTS, '%s.py' % name)
        back_name = os.path.join(PATH_TESTS, '%s.bak' % name)
        if os.path.exists(back_name):
            os.rename(back_name, test_name)
        return self


class Run(subprocess.Popen):

    def __init__(self, repertoire):
        results = open(PATH_REGRTEST_STDOUT, 'w')
        env = os.environ
        env.setdefault('PYTHONPATH', PATH_TEST_PACKAGE)
        subprocess.Popen.__init__(
            self,
            '%s/regrtest.py -f %s' % (PATH_TESTS, repertoire.file_name),
            stdout=results,
            env=env,
            shell=True,
            )
        results.close()
        return
        

def main_line():
    Archive(PATH_ARCHIVE).extract(PATTERN_MEMBER, PATH_TARGET)
    repertoire = Repertoire()
    repertoire.get_from_dir()
    repertoire.spike()
    repertoire.save()
    repertoire.load()
    repertoire.omit()
    repertoire.untidy_all()
    repertoire.tidy_all()
    process = Run(repertoire)
    retcd = process.wait()
    repertoire.log_results()
    repertoire.save()
    return


if __name__ == "__main__":
    main_line()


# Fin
