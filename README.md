NetMatch - The End
==================
**NetMatch** is a multiplayer online DeathMatch-game. There are weapons available with
differences in characteristics: Double hand guns, a sub-machine gun (SMG), bazooka, shotgun, grenade
launcher and chainsaw. You can collect extra ammo and medical kits from the field to be more
efficient on the battle field. Only the pistols carry infinite ammunition.

NetMatch also features Team DeathMatch mode where players are separated to two teams, green and red,
which fight against the other.

The ranking of players is based on kill/death -ratio. Plenty of kills and few deaths will place the
player on a higher position in the statistics.

In addition to human players, computer controlled AI-characters are found on the field.

How to compile
--------------
Just run the `compile_it_magically.bat` batch-file and it will handle all compiling, and it will
start the game for you, too. You can give one parameter to the batch-file indicating the name
of the generated `.exe`-file, without the `.exe` file extension.

*If the batch-file destroys your computer, too bad. The software is provided "as-is", without any
express or implied warranty.*

How to compile manually
-----------------------
To build this you need to get [CoolBasic](http://www.coolbasic.com) and change the compiler to the
one in this repository. **If the modded CBCompiler destroys your computer, too bad. The software is
provided "as-is", without any express or implied warranty.** The file that you need to build is
`cb_source\Netmatch_TheEnd.cb`. Build it to the root of this project.

Please note that you also need to build yourself a `NetMatch.dat`-file from the media-files.
Instructions are found in here: [media/README.md](https://github.com/VesQ/NetMatch/blob/master/media/README.md)

Coding conventions
------------------
* No tabs; use 4 spaces instead.
* Use CamelCaps for functions and variables.
* Extra rules for writing variables:
  - Variables should be named in english.
  - Small g prefix must be provided for globals (e.g. gPlayMode).
  - Constants must be all uppercase with an underscore for separating words (e.g. NM_VERSION).
* **The constant NM_DEVBUILD in the main source file (NetMatch_TheEnd.cb) MUST be set to 1
  when the version is not an official release!**
* Before pushing always check whether your code actually compiles.
* Please do not use colons ":" to put more code in one line. Just put the code to separate lines.
* Do **NOT** use Goto's or GoSub's, they're awful coding practice. Commits that use them won't get applied.
  - An exception to these two rules above is made in server-side coding (Server.cb file) in order to
    gain maximum performance. Colons are used so that the code looks cleaner.
