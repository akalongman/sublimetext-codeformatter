from setuptools import setup
from config import version, summary


install_requires = ['setuptools']
try:
    import argparse
    argparse    # make pyflakes happy
except ImportError:
    install_requires.append('argparse')


setup(name='PythonTidy',
      version=version,
      description=summary,
      long_description=summary,
      classifiers=[
          "Development Status :: 4 - Beta",
          "Environment :: Console",
          "Intended Audience :: Developers",
          "License :: OSI Approved :: GNU General Public License (GPL)",
          "Operating System :: OS Independent",
          "Programming Language :: Python :: 2",
          "Topic :: Software Development :: Quality Assurance",
        ],
      keywords='indentation beautify',
      author='Charles Curtis Rhode',
      author_email='CRhode@LacusVeris.com',
      url='http://pypi.python.org/pypi/PythonTidy',
      license='GPL version 2',
      py_modules=['PythonTidy', 'PythonTidyWrapper', 'config', 'runner'],
      platforms='Any',
      install_requires=install_requires,
      entry_points={
          'console_scripts': ['pythontidy = runner:main'],
      },
)
