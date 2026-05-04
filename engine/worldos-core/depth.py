import re
lines = open('src/universe.rs').readlines()
d = 0
for i, l in enumerate(lines):
    d += l.count('{') - l.count('}')
    if re.match(r'^\s*(pub |fn |impl |mod )', l):
        print(f'Line {i+1} : depth {d} : {l.strip()}')
