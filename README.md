# json-csv
System that converts each JSON file to CSV and saves it to disk.

Needs to open config/config.php and set up:

$ _CONFIG_JSON_FOLDER_PATH - the folder where JSON files(to be converted) will be saved on your server, as well as the URL of the source from where they were loaded (the source URL is saved in a special sources.json file, located in this folder, file that is not to be missed because is needed for 'check for updates')

$ _CONFIG_OUTPUT_FOLDER - the folder where the converted files are saved on your server

$ _CONFIG_EXPORT_TYPES - what types of conversion can be made. Until now CSV, TSV and XML are accepted.

Once the configuration is done, you can use test.php GUI and there are two text boxes that need to be filled in:

1. JSON URL - which is the source of JSON
2. OUTPUT FILENAME - the name with which the converted JSON file will be saved.

After clicking the convert button, in case there are no errors, a success message and a DOWNLOAD link of the converted file will be displayed.
