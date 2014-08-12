Steadfast
===============

A port of Pocketmine 1.3 to use the 0.9 protocol. By @zhuowei, @KnownUnown & @williamtdr. Base is from the [PocketMine Team](https://github.com/Pocketmine)

Does __not__ support infinite worlds! Intended for maximum compatibility with Pocketmine 1.3 systems.

__Features:__
* Supports Pocketmine 1.3 (API 12) plugins
* Performance levels similar to Pocketmine 1.3 servers
* Automatic server restart & log file cleanup

__Extra options:__
* Load plugin on server start from an FTP server
* Disable server logs to save CPU resources
* Disable player death messages

__To-do:__
* Threaded chunk sending
* Full 0.9 block & item support

__Known bugs:__
* Sometimes client will crash before reaching 'building terrain' screen and joining the server
* Players' positions update way less frequently than the used to, causes slower movement
* Improper lighting under certain areas
* After switching worlds, the new terrain does not render on the client before a block is broken or apps are switched - client bug, working on a workaround
* Under some areas of maps with blocks above, lighting is not proper.