#!/usr/bin/python
# -*- coding: utf-8 -*-

# BatchTidy.py
# Kevin Horton . 2008 Mar 27

"""Run pythontidy on globbed list of arguments, making backups.

For example:

> ./BatchTidy --suffix=\"~\" *.py

"""

# 2009 Oct 27 . ccr . Call PythonTidy through PythonTidyWrapper.
#                   . Skip copy if backup already exists.
#                   . Abort on error.
#                   . Add option to print shell script rather than run
#                   . tidy in real time.

from __future__ import division
import os
import sys
from optparse import OptionParser

ZERO = 0
SPACE = ' '
NULL = ''
NUL = '\x00'
NA = -1

PROC_PATH = os.path.split(sys.argv[ZERO])[ZERO]
PROC = os.path.join(PROC_PATH, 'PythonTidyWrapper.py')


def parse_options():
    """Parse the command line options.
    
    """

    global OPTIONS, ARGS
    usage = 'usage: %prog [options] arg'
    parser = OptionParser(usage=usage)
    parser.add_option(
        '-d',
        '--dry_run',
        action='store_true',
        dest='dry_run',
        default=False,
        help='dry_run mode.  Sends output to stdout.',
        )
    parser.add_option(
        '-i',
        '--in_place',
        action='store_true',
        dest='in_place',
        default=False,
        help='modify files in place mode.  Each file is overwritten with no backup.',
        )
    parser.add_option(  # 2009 Oct 27
        '-r',
        '--restore',
        action='store_true',
        dest='restore',
        default=False,
        help='restore mode.  Replace files with their untidy backups.',
        )        
    parser.add_option(
        '-s',
        '--suffix',
        dest='suffix',
        default='.bak',
        help='suffix for file backup.  (Defaults to ".bak")',
        )
    parser.add_option(  # 2009 Oct 27
        '-l',
        '--list_only',
        action='store_true',
        dest='list_only',
        default=False,
        help='list mode.  Generate a shellscript on stdout.',
        )
    parser.add_option(  # 2009 Oct 27
        '-u',
        '--ini_file',
        dest='ini_file',
        default=None,
        help='PythonTidyWrapper ini_file.',
        )
    (OPTIONS, ARGS) = parser.parse_args()
    if len(ARGS) < 1:
        parser.error('Please specify the files to tidy.')
    return


class Process(object):  # 2009 Oct 27

    def __init__(self, command):
        self.command = command
        return

    def run(self):
        print self.command
        if OPTIONS.list_only:
            pass
        else:
            result = os.system(self.command)
            if result is ZERO:
                pass
            else:
                print '#Error:  Command failed.'
                sys.exit(0x10)
        return self


class ProcessTidy(Process):

    def __init__(self, *parms):
        parms = list(parms)
        if OPTIONS.ini_file is None:
            pass
        else:
            ini_file = '"%s"' % OPTIONS.ini_file
            parms.insert(ZERO, ini_file)
            parms.insert(ZERO, '-u')
        parms.insert(ZERO, PROC)
        Process.__init__(self, SPACE.join(parms))
        return


class ProcessMakeExecutable(Process):

    def __init__(self, file_name):
        Process.__init__(self, 'chmod +x %s' % file_name)
        return


class ProcessBackup(Process):

    def __init__(self, file_name, bu_name):
        Process.__init__(
            self,
            'if test ! -e %s; then cp -a %s %s; fi' % (bu_name, file_name, bu_name),
            )
        return


class ProcessRestore(Process):

    def __init__(self, file_name, bu_name):
        Process.__init__(self, 'mv %s %s' % (bu_name, file_name))
        return


def tidy():
    """Run pythontidy on the specified files.
    
    """

    for arg in ARGS:
        arg_quote = '"%s"' % arg
        if OPTIONS.dry_run:
            ProcessTidy(arg_quote).run()  # Results to stdout.
        elif OPTIONS.in_place:
            ProcessTidy(arg_quote, arg_quote).run()
            ProcessMakeExecutable(arg_quote).run()
        elif OPTIONS.restore: 
            bu_file = '"%s%s"' % (arg, OPTIONS.suffix)
            ProcessRestore(arg_quote, bu_file).run()
        else:
            bu_file = '"%s%s"' % (arg, OPTIONS.suffix)
            ProcessBackup(arg_quote, bu_file).run()
            ProcessTidy(arg_quote, arg_quote).run()
            ProcessMakeExecutable(arg_quote).run()
    return

if __name__ == '__main__':
    parse_options()
    tidy()

# Fin!
