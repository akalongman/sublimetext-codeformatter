
from sys import stdin, stdout, exit
from config import version, summary
from PythonTidy import tidy_up
from PythonTidyWrapper import Config
import argparse

parser = ArgumentParser(description=summary)
parser.add_argument('input', nargs='?', type=FileType('r'), default=stdin,
    help='specify input file instead of `stdin`')
parser.add_argument('output', nargs='?', type=FileType('w'), default=stdout,
    help='specify output file instead of `stdout`')
parser.add_argument('-v', '--version', action='version',
    version='%%(prog)s %s' % version)
parser.add_argument('-d', '--dump', action='store_true',
    help='dump default configuration parameters')
parser.add_argument('-c', '--config', default=None,
    help='read configuration parameters from file')


def main():
    args = parser.parse_args()
    if args.dump:
        config = Config()
        config.from_pythontidy_namespace()
        config.write(file=stdout)
        exit()
    if args.config:
        config = Config(file=args.config)
        config.to_pythontidy_namespace()
    tidy_up(args.input, args.output)


if __name__ == "__main__":
    main()
