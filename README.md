# shealth2tcx
Convert Samsung S-Health exported data to tcx-format

## Prerequisites
You need to put s-health in "Developer mode".

Go to <http://developer.samsung.com/health> and download the Samsung Digital Health SDK

Extract the files and read the ProgrammingGuide_SHealthService.pdf to find out how to put S Health into "Developer Mode"

From the Healthdata / tools install the DataViewer-1.2.0.apk to get the dataviewing app which can export to CSV.

Transfer the exported data to your computer.

Then run `php shealth2tcx.php path/to/your/exported/com.samsung.health.exercise.{date}.csv`
