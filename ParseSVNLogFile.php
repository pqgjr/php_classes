<?php

class ParseSVNLogFile
{
   public $pathinfo;
   public $commits;
   public $record_delimit;
     
   const CR = "\r";
   const LF = "\n";
   
   public function __construct()
   {
      $this->record_delimit = str_repeat('-', 72);
      $this->targets_delimit = "Changed paths:";
   }
   
   public function process($filepath)
   {
      $this->pathinfo = pathinfo($filepath);
      $this->pathinfo['fullpath'] = $filepath;
      $fh = fopen($filepath, 'r');
      if ($fh)
      {
         echo "Processing: $filepath\n";
         $item_num = 0;
         $target_num = 0;
         $parse_state = 0;
         $field = '';
         $fields = array();
         $targets = array();
         while (false !== ($char = fgetc($fh)))
         {
            switch ($char)
            {
               case '|':
                  $fields[$parse_state] = trim($field);
                  $parse_state++;
                  $field = '';
                  break;
               case self::CR:
                  break;
               case self::LF:
                  if (0 < strlen($field))
                  {
                     if ($this->targets_delimit === $field)
                     {
                        $target_num = 1;
                        $parse_state = 6;
                     }
                     elseif ($this->record_delimit === $field)
                     {
                        if (0 !== $parse_state)
                        {
                           $this->commits[$item_num]['revision'] = substr($fields[1], 1);
                           $this->commits[$item_num]['author'] = $fields[2];
                           $this->commits[$item_num]['timestamp'] = $fields[3];
                           $this->commits[$item_num]['lines'] = sscanf($fields[4], "%d %s")[0];
                           
                           if (array_key_exists(5, $fields))
                           {
                              $this->commits[$item_num]['description'] = $fields[5];
                           }
                           else
                           {
                              $this->commits[$item_num]['description'] = '(no description)';
                           }
                           if (0 < count($targets))
                           {
                              $this->commits[$item_num]['targets'] = $targets;
                           }
                           $item_num++;
                           $fields = array();
                           $targets = array();
                        }
                        $parse_state = 1;
                     }
                     else
                     {
                        switch ($parse_state)
                        {
                           case 4:
                              $fields[$parse_state] = trim($field);
                              $parse_state++;
                              break;
                           case 5:
                              if (array_key_exists($parse_state, $fields))
                              {
                                 $fields[$parse_state] .= sprintf(" %s", trim($field));
                              }
                              else
                              {
                                 $fields[$parse_state] = sprintf("%s", trim($field));
                              }
                              break;
                           case 6:
                              list($action, $target) = explode(" ", trim($field));
                              if ((0 === strlen($action)) && (0 === strlen(target)))
                              {
                                 $parse_state = 5;
                              }
                              else
                              {
                                 $targets[$target_num]['action'] = $action;
                                 $targets[$target_num]['target'] = $target;
                                 $target_num++;
                              }
                              break;
                           default:
                              echo "Unexpected parse_state=$parse_state\n";
                              exit();
                              break;
                        }
                     }
                  }
                  else 
                  {
                     $parse_state = 5;
                  }
                  $field = '';
                  break;
               default:
                  $field .= $char;
                  break;
            }
         }
         fclose($fh);
      }
   }
   
   public function create_csv($filepath)
   {
      $records = '';
      foreach ($this->commits as $item_num => $commit)
      {
         if (array_key_exists('targets', $commit))
         {
            foreach ($commit['targets'] as $target_num => $targetdata)
            {
               $records .= sprintf("%d\t", $item_num);
               $records .= sprintf("%d\t", $commit['revision']);
               $records .= sprintf("%s\t", $commit['author']);
               $records .= sprintf("%s\t", $commit['timestamp']);
               $records .= sprintf("%s\t", $commit['description']);
               $records .= sprintf("%s\t", $targetdata['action']);
               $records .= sprintf("%s\n", $targetdata['target']);
            }
         }
         else 
         {
            $records .= sprintf("%d\t", $item_num);
            $records .= sprintf("%d\t", $commit['revision']);
            $records .= sprintf("%s\t", $commit['author']);
            $records .= sprintf("%s\t", $commit['timestamp']);
            $records .= sprintf("%s\n", $commit['description']);
         }
      }
      file_put_contents($filepath, $records);
   }
}

/*********************************************************************************/
$path = "C:/Users/guest14";
$url = 'http://debesvn001/kostal/lk_ae/CR/RU/';
$subcommand = "log";
if (1)
{
   /* No additional options */
   $filename = "svn_ru";
   $infilepath = sprintf("%s/%s.%s", $path, $filename, "log");
   $outfilepath = sprintf("%s/%s.%s", $path, $filename, "csv");
   $options = '';
   $return = 0;
   if (1)
   {
      /* Execute SVN command */
      $cmd = sprintf("svn %s %s %s > %s", $subcommand, $options, $url, $infilepath);
      echo "Executing command:\n$cmd\n";
      exec($cmd, $output, $return);
      echo "done.\n";
   }
   if (1)
   {
      /* Parse SVN log file */
      if (0 === $return)
      {
         $psvnlf = new ParseSVNLogFile();
         $psvnlf->process($infilepath);
         $psvnlf->create_csv($outfilepath);
         echo "ParseSVNLogFile Done!\n";
      }
      else
      {
         echo "svn command failed. return=$return\n";
      }
   }
}
if (1)
{
   /* -v Verbose option */
   $filename = "svn_ru_verbose";
   $infilepath = sprintf("%s/%s.%s", $path, $filename, "log");
   $outfilepath = sprintf("%s/%s.%s", $path, $filename, "csv");
   $options = '-v';
   $return = 0;
   if (1)
   {
      /* Execute SVN command */
      $cmd = sprintf("svn %s %s %s > %s", $subcommand, $options, $url, $infilepath);
      echo "Executing command:\n$cmd\n";
      exec($cmd, $output, $return);
      echo "done.\n";
   }
   if (1)
   {
      /* Parse SVN log file */
      if (0 === $return)
      {
         $psvnlf = new ParseSVNLogFile();
         $psvnlf->process($infilepath);
         $psvnlf->create_csv($outfilepath);
         echo "ParseSVNLogFile Done!\n";
      }
      else
      {
         echo "svn command failed. return=$return\n";
      }
   }
}
echo  "All done!\n";

?>
