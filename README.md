# OpenCart Currency Converter Module

This module is a tool that automatically converts product prices from USD to TRY in your OpenCart e-commerce system. It updates prices using current exchange rates from the Central Bank of Turkey (CBRT).

## Features

- Fetches current USD/TRY exchange rate from CBRT
- Bulk product price updates
- Product search and filtering
- Journal theme compatible interface
- Product image preview
- Error handling and logging
- Multi-language support (TR/EN)

## Requirements

- OpenCart 3.x
- PHP 7.2 or higher
- Journal 3.x Theme (optional)
- cURL support
- SimpleXML support

## Installation

1. Upload all files to your OpenCart root directory
2. Go to Extensions > Modules in the admin panel
3. Find the Currency Converter module and click Install
4. Enable the module

## Usage

1. Go to Extensions > Modules > Currency Converter in the admin panel
2. Select the products you want to update
3. Click "Update Selected Products"
4. Prices will be automatically updated with the current USD/TRY exchange rate

## Security

- Module uses the official CBRT XML service
- All AJAX requests undergo token verification
- SQL injection protection
- User permission control


## License

This module is distributed under the GNU/GPL   License. See LICENSE file for details.

## Version History

### 1.0.0 (2024-03-20)
- Initial release
- Added core features
- Journal theme integration

## Contributing

1. Fork this repository
2. Create your feature branch (`git checkout -b feature/NewFeature`)
3. Commit your changes (`git commit -am 'Add new feature: Details'`)
4. Push to the branch (`git push origin feature/NewFeature`)
5. Create a Pull Request

## Acknowledgments

- OpenCart Community


## Screenshots
![alt text](https://github.com/MorphyKutay/CS2-ESP-Cheat/blob/main/ss.png)