# Setup
Go to `plugin_data/Areas/config.yml` and setup your areas, the message to display and the permissions to grant/remove when entering/leaving the area

# Config:
```yaml
---
# Customize messages, use {AREA} if you want to display the area name. Leave them blank if you don't want to send a message
message-entering: "§aEntering area {AREA}" # The message to be sent to the player when entering the area
message-leaving: "§cLeaving area {AREA}" # The message to be sent to the player when leaving the area

areas:
  area1: # Area name
    x1: 100 # X coord of the first position
    y1: 64  # Y coord of the second position
    z1: 100 # Z coord of the first position
    x2: 200 # X coord of the second position
    y2: 70  # Y coord of the second position
    z2: 200 # Z coord of the second position
    world: "world" # World's folder name of this area

    # These are the current handled events. Set an event's value to null if you don't want the plugin to interfere with it, because if
    # an event is cancelled by another plugin (or PM itself) but you set that event to true here, the event will be uncancelled.
    blockPlace: false # Wheter block placing is allowed in this area
    blockBreak: false # Whether block breaking is allowed in this area
    blockInteract: true # Whether interacting with blocks (chests, doors, etc) is allowed in this area
    entityDamage: false # Whether entities can be damaged in this area (this includes PVP, PVE and natural damage)

    # A list of the effects given when entering this area, and removed when leaving it.
    # You should put the name of the effect, then a hyphen (-), then the level of the effect.
    effects:
      - "speed-4"
  area2: # You can add more areas following this format
    x1: 100
    y1: 64
    z1: 100
    x2: 200
    y2: 70
    z2: 200
    world: "world"
    blockPlace: false
    blockBreak: false
    blockInteract: true
    entityDamage: false
    effects:
      - "speed-4"
```
