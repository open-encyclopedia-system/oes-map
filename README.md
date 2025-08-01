# OES MAP Module
Welcome to the Open Encyclopedia System (OES) Map repository on GitHub.  
OES is a modular and configurable software platform for creating, publishing, and maintaining online encyclopedias in the humanities and social sciences. It is designed to be accessible worldwide through Open Access.

For more information, please visit the [main repository](https://github.com/open-encyclopedia-system) or our [website](https://open-encyclopedia-system.org).

A typical OES application consists of:
- the **OES Core plugin**
- a **project-specific OES plugin** that implements application-specific features
- optional **OES modules**, such as this module

The **OES Map** plugin displays a collection of places on a map using OpenStreetMap and Leaflet.

## Dependencies
This module depends on:

- **OES Core**, version `2.3.3`  
  Repository: [https://github.com/open-encyclopedia-system/oes-core](https://github.com/open-encyclopedia-system/oes-core)

- **Advanced Custom Fields (ACF)**, version `6.3.4`  
  Website: [https://www.advancedcustomfields.com](https://www.advancedcustomfields.com)

## Support
This repository does **not** offer public support or issue tracking.  
If you need help using the OES plugins, please contact our help desk:  
**info@open-encyclopedia-system.org**

For information about available modules, customization options, or help launching your own encyclopedia, visit:  
[https://open-encyclopedia-system.org](https://open-encyclopedia-system.org)

## Documentation
The full user and technical manual is available at:  
[https://manual.open-encyclopedia-system.org/](https://manual.open-encyclopedia-system.org/)

## Contributing
If you are interested in contributing to OES development, please get in touch:  
**info@open-encyclopedia-system.org**

## Credits
Developed by **Digitale Infrastrukturen**, Freie Universität Berlin (FUB IT),  
with support from the **German Research Foundation (DFG)**.

## Licencing
Copyright (C) 2025
Freie Universität Berlin, FUB IT, Digitale Infrastrukturen
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later
version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

## Roadmap
- provide blocks
- provide shortcode documentation for oes_map_archive_switch and oes_map_spinner
- prepare legend via js instead of using globals.

# Changelog

## 1.1.0
- remove single block
- Extended shortcode documentation 
- New popup pages: Multiple objects can now be browsed within a single popup 
- Extensible class OES\Map\Map 
- Updated Leaflet version and other dependencies