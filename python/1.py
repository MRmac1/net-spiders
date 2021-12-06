import requests

res = requests.get('https://www.python.org')

print('url request', res.text)