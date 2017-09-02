Remove-Item .\tmp* -Force -Recurse
New-Item -ItemType directory -Path .\tmp\kleistad
Copy-Item -Path .\admin -Destination .\tmp\kleistad\admin -recurse -Force
Copy-Item -Path .\public -Destination .\tmp\kleistad\public -recurse -Force
Copy-Item -Path .\includes -Destination .\tmp\kleistad\includes -recurse -Force
Copy-Item -Path .\*.php, .\*.txt -Destination .\tmp\kleistad\ -Force
Compress-Archive -Path .\tmp\kleistad -DestinationPath kleistad.zip -Force
Write-Host "Press any key to continue ..."
$x = $host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
