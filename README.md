NetMatch - The End
==================
<<<<<<< HEAD
<em>NetMatch</em> is a multiplayer online DeathMatch-game.
=======
<strong>NetMatch</strong> is a multiplayer online DeathMatch-game.
>>>>>>> 112a166cc500617d5d7afb1d23d5e65d622712b6
There are weapons available with differences in characteristics: Double hand guns, a sub-machine gun (SMG), bazooka, shotgun, grenade launcher and chainsaw.
You can collect extra ammo and medical kits from the field to be more efficient on the battle field.
Only the pistols carry infinite ammunition.

NetMatch also features Team DeathMatch mode where players are separated to two teams, green and red, which fight against the other.

The ranking of players is based on kill/death -ratio. Plenty of kills and few deaths will place the player on a higher position in the statistics.

In addition to human players, computer controlled AI-characters are found on the field. 

How to compile
--------------
<<<<<<< HEAD
To build this you need to get [CoolBasic](http://www.coolbasic.com) and alongiside with it, the [modded CBcompiler](http://www.coolbasic.com/phpBB3/viewtopic.php?f=9&t=1616) [[Direct link](http://koti.mbnet.fi/cerebro/CBCompiler_safe.zip)].
=======
To build this you need to get [CoolBasic](http://www.coolbasic.com) and change the compiler to the one coming withing this repository.
<strong>If the modded CBCompiler.exe destroys your computer, too bad. The software is provided "as-is", without any express or implied warranty.</strong>

How to compile a NetMatch.dat
-----------------------------
See here: [media/README.md](https://github.com/VesQ/NetMatch/blob/master/media/README.md)

Coding conventions
------------------
* No tabs; use 4 spaces instead.
* Use CamelCaps for functions and variables.
* Extra rules for writing variables:
  - Variables should be named in english.
  - Small g prefix must be provided for globals (e.g. gPlayMode).
  - Constants must be all uppercase with an underscore for separating words (e.g. NM_VERSION).
* <strong>The constant NM_DEVBUILD in the main source file (NetMatch_TheEnd.cb) MUST be set to 1
  when the version is not an official release!</strong>
* Before pushing always check whether your code actually compiles.
* Please do not use colons ":" to put more code in one line. Just put the code to separate lines.
