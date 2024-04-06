
[![](https://poggit.pmmp.io/shield.state/AreaPermissions)](https://poggit.pmmp.io/p/AreaPermissions)

# Setup
Go to `plugin_data/AreaPermissions/config.yml` and setup your areas, the message to display and the permissions to grant/remove when entering/leaving the area

# Config:
```yaml
---
# Customize messages, leave them blank if you don't want to send a message, use {AREA} if you want to display the area name
message-entering: "Entering area {AREA}" # The message to be sent to the player when entering the area
message-leaving: "Leaving area {AREA}" # The message to be sent to the player when leaving the area
areas:
  area1: # Area name
    x1: 100 # X coord of the first position
    y1: 64  # Y coord of the second position
    z1: 100 # Z coord of the first position
    x2: 200 # X coord of the second position
    y2: 70  # Y coord of the second position
    z2: 200 # Z coord of the second position
    world: "world" # World name of this area 
    permissions: # List of permissions that will be granted when entering the area and will be removed when leaving the area
      - permission1
      - permission2
  area2: # You can add more than one area following this format
    x1: 300
    y1: 64
    z1: 300
    x2: 400
    y2: 70
    z2: 400
    world: "world2"
    permissions:
      - permission3
      - permission4
```
