#!/usr/bin/perl

$newbuildlist = $ARGV[0];
$oldbuildlist = $ARGV[1];

$diff = "Diff4JoeyBuildList.txt";

system ("diff -w $newbuildlist $oldbuildlist > $diff");

$lineCount = 0;
open (DATAFILE, $diff);
while ($data_line = <DATAFILE>) {

  chomp ($data_line);

  if (($lineCount != 0) && ($data_line ne "< ")) {
      
      if ($data_line eq "> ") {
          print "ERROR. YOU CANNOT REMOVE DEVICES FROM BUILD LIST!!!\n";
          exit 1;
      }
 
      @data = split(/\t/, $data_line);
      
      $name = substr $data[0], 2;
      $locale = $data[1];
      $jar = $data[2];
      $jad = $data[3];
      $ua = $data[4];
      ($width, $height) = split(/x/, $data[5]);

      if ($jar)
      {
          # print "$name : $locale : $jar : $jad : $width : $height : $ua \n";
          print "INSERT INTO `phones` (name, locale, jar_name, jad_name, screen_width, screen_height) VALUES ('$name', '$locale', '$jar', '$jad', '$width', '$height'); \n";
      }
  }
  $lineCount++;
}

close (DATAFILE);
unlink ($diff);
