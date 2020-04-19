import requests
page = requests.get("http://52.203.150.214:8080/CONFUSALive/matches")
page

with open('pyscrap.txt', 'w') as file:
    file.write(page.content)