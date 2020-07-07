# Detect the use of short_open_tag in your PHP source code

Usage:
```
bin/detect_short_open_tag [-e|--extension php] <dir1>... <dirn>
Scans the given paths for PHP files containing the use of short open tags and reports them.
  -e|--extension    File extensions to scan, defaults to 'php'
  <dir>             One or more directories to scan fo
  The script will   exit with 0 if no short open tags where found and 1 otherwise.
```

Example output (compatible with Github Actions):
```
::error file=tests/mixed_open_tags.php,line=2::short open tag not allowed
::error file=tests/mixed_open_tags.php,line=4::short open tag not allowed
::error file=tests/only_short_open_tag.php,line=1::short open tag not allowed
::error file=tests/short_open_tag_other_file_extension.foo,line=1::short open tag not allowed
```
