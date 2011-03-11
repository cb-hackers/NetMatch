# -*- coding: utf-8 -*- 

import socket 
import time 
import os
from time import time
from urllib2 import urlopen, quote, URLError
from datetime import date
from threading import Timer


configFile    = "botconfig.ini"

# Asetetaan hieman oletusarvoja, jos kaikkia tietoja ei löydäkään config-filusta
defaultServer = "irc.saunalahti.fi"
defaultPort   = 6667
defaultRName  = " "
defaultMinCmdWait = 4
defaultTimeout = 3

# Varmistetaan config-filun olemassaolo
if os.path.exists(configFile) == False:
	exitErr('Config file not found')


lastHandledMsg = 0
nickId = 0

print "reading config..."

# asetusten luku alkaa
try:
	f=open(configFile, 'r')
	configData = f.read()
	f.close()
except:
	f.close()
	exitErr("wtf just happened")

cLines = configData.split('\n')
settings = {}
for cLine in cLines:
	if cLine.startswith('#')==False and cLine.find('=')>0:
		part = cLine.split('=')
		name = part[0].strip().lower()
		value= part[1].strip()
		if name=='irc_nick' or name=='list_url':
			settings[name] = value.split(',')
		else:
			settings[name] = value

if settings.has_key('irc_nick') == False:
	exitErr('No irc nick set in the config file')
if settings.has_key('irc_user') == False:
	exitErr('No irc user set in the config file')
if settings.has_key('irc_channel') == False:
	exitErr('No irc channel set in the config file')
if settings.has_key('list_url') == False:
	exitErr('No url for the bot listing set in the config file')
	
minCmdWait = int(settings.get('mincmdwait',defaultMinCmdWait))

# asetusten luku päättyy



# tulostaa virheen ja lopettaa ohjelman
def exitErr(msg):
	print msg
	exit()

# lähettää viestin kanavalle parametrit (kanava, viesti) 
def sendm(ch, msg): 
    send('PRIVMSG '+ch+' :'+msg)

# lähettää raakadataa servulle ja loppuun rivinvaihdon
def send(msg):
	sckIrc.send(msg+"\r\n")
	#print msg

# liittyy kanavalle :O
def join(ch):
	send("JOIN "+ch)

# poistuu kanavalta :o
def part(ch):
	send("PART "+ch)

# yrittää asettaa nickin, lopettaa ohjelman jos vapaata nikkiä ei löydy
def trySetNick():
	global nickId
	if nickId < len(settings['irc_nick']):
		send('NICK '+ settings['irc_nick'][nickId])
	else:
		exitErr("Nickname already in use")


# muodostetaan yhteys irc-palvelimeen
sckIrc = socket.socket(socket.AF_INET, socket.SOCK_STREAM) 
sckIrc.connect((settings.get('irc_server', defaultServer), int(settings.get('irc_port',defaultPort)))) 

send('USER '+settings.get('irc_user')+' 0 0 :'+settings.get('irc_rname',defaultRName)) 
trySetNick()

words = []


# käsittelee yhden rivin irc-messagen
def processMessage(line):
	line = line.rstrip('\r\n')
	if len(line) == 0:
		return
	
	words = line.split(' ')
	
	
	serverMsg = True # kertoo onko viestin lähettäjä serveri vai joku muu
	
	# asetellaan messagen tiedot nätisti "taulukkoon"
	msg = {}
	o=0
	start=0
	if words[0].find(':') != -1:
		if words[0].find('!') == -1:
			msg['nick'] = words[0][1:]
			msg['host'] = msg['nick']
		else:
			msg['nick'] = words[0][1:words[0].find('!')]
			msg['host'] = words[0][:len(msg['nick'])+1]
			serverMsg   = False
		o += len(words[0])+1
		start = 1
	msg['param'] = []
	
	r = range(start,len(words))
	c=0
	for i in r:
		if c == 0:
			msg['cmd'] = words[i].lower()
		elif words[i].find(':')==0:
			msg['message'] = line[o+1:]
			break
		else:
			msg['param'].append(words[i])
		o += len(words[i])+1
		c += 1
	
	if serverMsg == False:
		# ohjataan viestit oikeaan osoitteeseen
		messageHandler.get(msg['cmd'], unhandledMessage)(msg)
	else:
		# palvelimen viestit käsitellään erikseen
		handleServerMsg(msg)


# käsittelee palvelimen viestit
def handleServerMsg(msg):
	if msg.has_key('cmd') == False:
		return
	
	if msg['cmd'] == 'ping':
		send('PONG :'+msg['message'])
	elif msg['cmd'] == '376':
		join(settings.get('irc_channel'))
	elif msg['cmd'] == '433':
		nickId += 1
		trySetNick()

# tänne päätyvät kaikki paitsi serverin käsittelemättömät viestit
def unhandledMessage(msg):
	#print "unhandled message "+msg['cmd']
	pass

# käsitellään privmsg:t eli normaalit irkissä puhumiset
def handlePrivMsg(msg):
	global lastHandledMsg, minCmdWait
	if (time()-lastHandledMsg)<minCmdWait:
		return
	ch=msg['param'][0]
	
	# jos kanava on sama kuin oma nikki (ollaan privassa) niin pitää tietysti vastata lähettäjälle
	if ch.lower() == settings.get('irc_nick')[nickId].lower():
		ch = msg['nick']
	
	m = msg['message']+" "
	
	# komentojen käsittely alkaa
	if m.startswith('!'):
		c = m[1:m.find(' ')]
		lastHandledMsg = time()
		if c == 'list':
			listId = 0
			success = False
			while listId<len(settings['list_url']):
				try:
					data = urlopen(settings['list_url'][listId]).read().strip()
					if data.startswith('GSS:')==False:
						listId+=1
						continue
					if len(data)==4:
						success = True
						sendm(ch, "No servers online.")
						break
					for srv in data[4:].split('\n'):
						d={}
						for exp in srv.split('|'):
							tmp = exp.split('=')
							if tmp[1].find(',')!=-1:
								tmp[1]=tmp[1].split(',')
							d[tmp[0]]=tmp[1]
						maxPl = int(d['info'][3])
						totPl = int(d['info'][0])
						botPl = int(d['info'][1])
						sendm(ch, "%s - %s - map:%s - players:%d(%d)/%d" % (d['name'], d['ver'], d['info'][2], (totPl-botPl), totPl, maxPl))
					success = True
					break
				except URLError, e:
					listId+=1
				except:
					pass
			if success == False:
				sendm(ch,"Got error while getting server list :(")


# tällä ohjataan komennot oikeille funktioille
messageHandler = {'privmsg' : handlePrivMsg}

print "probably connected to "+settings.get('irc_server', defaultServer)

recv=''
# aloitetaan yhteyslooppi
while 1:
	recv+=sckIrc.recv(1024)
	
	# jos data ei lopu rivinvaihtoon, se jäi kesken, joten ei prosessoida sitä vielä
	if len(recv) == 0 or recv.endswith("\r\n") == False:
		continue
	
	if recv.find("\r\n") != False:
		for line in recv.split("\r\n"):
			processMessage(line)
	else:
		processMessage(line)
	recv=''