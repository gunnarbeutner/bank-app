#!/usr/bin/perl -w
# nagios: -epn
#
# COPYRIGHT:
#  
# This software is Copyright (c) 2009 NETWAYS GmbH, Birger Schmidt
#                                <info@netways.de>
#      (Except where explicitly superseded by other copyright notices)
# 
# LICENSE:
# 
# This work is made available to you under the terms of Version 2 of
# the GNU General Public License. A copy of that license should have
# been provided with this software, but in any event can be snarfed
# from http://www.fsf.org.
# 
# This work is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
# 02110-1301 or visit their web page on the internet at
# http://www.fsf.org.
# 
# 
# CONTRIBUTION SUBMISSION POLICY:
# 
# (The following paragraph is not intended to limit the rights granted
# to you to modify and distribute this software under the terms of
# the GNU General Public License and is only of importance to you if
# you choose to contribute your changes and enhancements to the
# community by submitting them to NETWAYS GmbH.)
# 
# By intentionally submitting any modifications, corrections or
# derivatives to this work, or any other work intended for use with
# this Software, to NETWAYS GmbH, you confirm that
# you are the copyright holder for those contributions and you grant
# NETWAYS GmbH a nonexclusive, worldwide, irrevocable,
# royalty-free, perpetual, license to use, copy, create derivative
# works based on those contributions, and sublicense and distribute
# those contributions and any derivatives thereof.
#
# Nagios and the Nagios logo are registered trademarks of Ethan Galstad.



######################################################################
######################################################################
#
# configure here to match your system setup
#
my $object_cache	= "/usr/local/icinga/var/objects.cache";
my $nagios_cmd		= "/usr/local/icinga/var/rw/icinga.cmd";
my $logfile			= "/usr/local/icinga/var/smsfinder.log";

my $ok = "^OK ";
my $ack = "^ACK ";

#my $ok = "Antwort ist JA";
#my $ack = "Antwort ist NEIN";

#
# don't change anything below here
#
######################################################################
######################################################################


use strict;
use warnings;
use Getopt::Long qw(:config no_ignore_case bundling);
use File::Basename;
use IO::Socket;
use XML::Simple;

our @state = ('OK', 'WARNING', 'CRITICAL', 'UNKNOWN');

my $HowIwasCalled			= "$0 @ARGV";

# version string
my $version					= '0.3';

my $basename				= basename ($0);

# init command-line parameters
my $hostaddress				= undef;
my $timeout					= 60;
my $warning					= 40;
my $critical				= 20;
my $show_version			= undef;
my $verbose					= undef;
my $help					= undef;
my $user					= undef;
my $pass					= undef;
my $number					= undef;
my $splitmax				= 1;
my $noma					= 0;
my $message					= 'no text message given';
my $contactgroup				= undef;

my @msg						= ();
my @perfdata				= ();
my $exitVal					= undef;
my $loginID					= '0';
my $do_not_verify			= 0;


my %smsErrorCodes = (
#Error Code, Error Description
601,'Authentication Failed',
602,'Parse Error',
603,'Invalid Category',
604,'SMS message size is greater than 160 chars',
605,'Recipient Overflow',
606,'Invalid Recipient',
607,'No Recipient',
608,'SMSFinder is busy, can’t accept this request',
609,'Timeout waiting for a TCP API request',
610,'Unknown Action Trigger',
611,'Error in broadcast Trigger',
612,'System Error. Memory Allocation Failure',
);

sub mypod2usage{
    # Load Pod::Usage only if needed.
    require "Pod/Usage.pm";
    import Pod::Usage;

	pod2usage(@_);
}

# get command-line parameters
GetOptions(
   "H|hostaddress=s"		=> \$hostaddress,
   "t|timeout=i"			=> \$timeout,
   "v|verbose"				=> \$verbose,
   "V|version"				=> \$show_version,
   "h|help"					=> \$help,
   "u|user=s"				=> \$user,
   "p|password=s"			=> \$pass,
   "n|number=s"				=> \$number,
   "s|splitmax=i"			=> \$splitmax,
   "noma"					=> \$noma,
   "o|objectcache=s"		=> \$object_cache,
   "m|message=s"			=> \$message,
   "w|warning=i"			=> \$warning,
   "c|critical=i"			=> \$critical,
   "g|contactgroup=s"			=> \$contactgroup,
) or mypod2usage({
	-msg     => "\n" . 'Invalid argument!' . "\n",
	-verbose => 1,
	-exitval => 3
});

sub printResultAndExit {

	# print check result and exit

	my $exitVal = shift;

	print "@_" if (defined @_);

	print "\n";

	# stop timeout
	alarm(0);

	exit($exitVal);
}

if ($show_version) { printResultAndExit (0, $basename . ' - version: ' . $version); }

mypod2usage({
	-verbose	=> 1,
	-exitval	=> 3
}) if ($help);

mypod2usage({
	-msg	    => "\n" . 'Warning level is lower than critical level. Please check.' . "\n",
	-verbose	=> 1,
	-exitval	=> 3
}) if ($warning < $critical);



# set timeout
local $SIG{ALRM} = sub {
	if (defined $exitVal) {
		print 'TIMEOUT: ' . join(' - ', @msg) . "\n";
		exit($exitVal);
	} else {
		print 'CRITICAL: Timeout - ' . join(' - ', @msg) . "\n";
		exit(2);
	}
};
alarm($timeout);


sub urlencode {
	my $str = "@_";
	$str =~ s/([^A-Za-z0-9])/sprintf("%%%02X", ord($1))/seg;
	return $str;
}

sub urldecode {
	my $str = "@_";
	$str =~ s/\%([A-Fa-f0-9]{2})/pack('C', hex($1))/seg;
	return $str;
}

sub prettydate { 
# usage: $string = prettydate( [$time_t] ); 
# omit parameter for current time/date 
   @_ = localtime(shift || time); 
   return(sprintf("%04d/%02d/%02d %02d:%02d:%02d", $_[5]+1900, $_[4]+1, $_[3], @_[2,1,0])); 
} 

sub justASCII {
	join("",
		map { # german umlauts
			chr($_) eq 'ö' ? 'oe' :
			chr($_) eq 'ä' ? 'ae' :
			chr($_) eq 'ü' ? 'ue' :
			chr($_) eq 'Ö' ? 'Oe' :
			chr($_) eq 'Ä' ? 'Ae' :
			chr($_) eq 'Ü' ? 'Ue' :
			chr($_) eq 'ß' ? 'ss' :
			$_ > 128 ? '' :						# cut out anything not 7-bit ASCII
			chr($_) =~ /[[:cntrl:]]/ ? '' :		# and control characters too
			chr($_)								# just the ASCII as themselves
	} unpack("U*", $_[0]));						# unpack Unicode characters
}  

sub httpGet {
	my $document = shift;
	my $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "http(80)");
	if ($remote) { 
		$remote->autoflush(1);
		print $remote "GET $document HTTP/1.1\015\012\015\012";
		#my $http_answer = join(' ', (<$remote>));
		my $http_answer;
		while (<$remote>) {
			$http_answer .= $_;
		}
		close $remote;
		$http_answer =~ tr/\n\r/ /;
		if ($verbose) { print 'SMSFinder response  : ' . $http_answer . "\n"; }
		return $http_answer;
	} else {
		return undef;
	}
}

sub httpPostLogin {
	my $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "http(80)");
	if ($remote) { 
		$remote->autoflush(1);
		my $poststring = "fileName=index.html&userName=$user&password=$pass";
		print $remote 
			"POST /cgi-bin/postquery.cgi HTTP/1.1\015\012" .
			"Content-Length: " .  length($poststring) .  "\015\012" . 
			"Content-Type: application/x-www-form-urlencoded\015\012" . 
			"\015\012" . 
			$poststring;
		close $remote;
		my $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "http(80)");
		if ($remote) {
			print $remote "GET /index.html?0 HTTP/1.1\015\012\015\012";
			while ( <$remote> ) { 
				if (/url="(?:home|smsSend).html\?(\d+)"/) { 
					$loginID = $1;
					if ($verbose) { print 'SMSFinder login ID : ' . $loginID . "\n"; }
					push (@perfdata, "loginID=$loginID");	
					last;
				}
			}
		close $remote;
		}
		return ($loginID != 0);
	} else {
		return undef;
	}
}

sub httpGetLogout {
	my $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "http(80)");
	if ($remote) {
		print $remote "GET /logout.html?$loginID HTTP/1.1\015\012\015\012";
		while ( <$remote> ) { 
				if ($verbose) { print 'SMSFinder logout : ' . $_ . "\n"; }
			}
		}
	close $remote;
}

sub httpOutput {
	my ($output) = @_;

	print "Content-Type: text/plain\r\n\r\n$output\r\n";
}

sub httpDie {
	my ($output) = @_;
	
	die "$output" if (*LOG eq *STDERR);
	httpOutput($output);
	print LOG prettydate() . ' ' . $output . "\n";
	die;
}

sub httpPostReboot {
	my $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "http(80)");
        if ($remote) {
		$remote->autoflush(1);
                # Save to flash before rebooting
                my $poststring = "filename=save_restartLoading.html%3F$loginID&commandVal=set+save_conf&userid=$loginID";
                print $remote 
                  "POST /cgi-bin/postquery.cgi HTTP/1.1\015\012" .
                  "Content-Length: " .  length($poststring) .  "\015\012" . 
                  "Content-Type: application/x-www-form-urlencoded\015\012" . 
                  "\015\012" .
                  $poststring;
                while ( <$remote> ) { 
                  if ($verbose) { print 'SMSFinder save to flash : ' . $_; }
                }
                close $remote;
                sleep(30);
                # Reboot
                $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "http(80)");
                $poststring = "filename=save_restartLoading.html%3F$loginID&commandVal=set+reboot_box&userid=$loginID";
                print $remote 
                  "POST /cgi-bin/postquery.cgi HTTP/1.1\015\012" .
                  "Content-Length: " .  length($poststring) .  "\015\012" . 
                  "Content-Type: application/x-www-form-urlencoded\015\012" . 
                  "\015\012" .
                  $poststring;
                while ( <$remote> ) { 
                  if ($verbose) { print 'SMSFinder reboot : ' . $_; }
                }
                close $remote;
                # Check to see if reboot worked
                sleep(30);
                unless (httpPostLogin) {
                	print "ERROR: SMS login failed!\n";
                        return undef;
                }
                my $response = httpGet('/statsSysinfo.html' . "?$loginID");
		if (defined $response) {
                  	# if uptime is < 1 minute, assume reboot worked
			if ($response =~ /200 OK.*?System Uptime.*>(0 Days, 0 Hours, 0 Minutes, \d+ Seconds)/) {
                          	httpGetLogout;
                                return 1;
                        } else {
                        	$response =~ /200 OK.*?System Uptime.*>(\d+ Days, \d+ Hours, \d+ Minutes, \d+ Seconds)/;
                        	print "Uptime: $1\n";
                        	return undef;
                        }
		} else {
			print "ERROR: cannot contact modem after reboot!\n";
                        return undef;
		}
		return ($loginID != 0);
	} else {
		return undef;
        }
}

sub telnetRW {
	my $command = shift;
	my $remote = IO::Socket::INET->new(Proto => "tcp", PeerAddr => $hostaddress, PeerPort => "5000");
	if ($remote) { 
		$remote->autoflush(1);
		print "$command\n";
		print $remote "$command\015";
		my $answer;
		my $char;
		while ($remote->read($char,1)) { 
			$answer .= $char; 
			#print ".$char";
			if ($answer =~ /(OK|ERROR)(.*)\015\012/) {
				last;
			}
		} 
		close $remote;
		$answer =~ tr/\n\r/ /;
		if ($verbose) { print 'SMSFinder response  : ' . $answer . "\n"; }
		return $answer;
	} else {
		return undef;
	}
}


#
# chose one of the possible functions of this script
#

if ($basename eq 'email2sms.pl') {
# convert e-mail to SMS
       open (LOG, ">>".$logfile) or *LOG = *STDERR;
       print LOG prettydate(); 
       print LOG " SMSemail: $HowIwasCalled\n"; 
       close LOG;

        # use Mail::Internet and Mail::Header to parse e-mail message
        use Mail::Internet;
        use Mail::Header;

        my $email = Mail::Internet->new(\*STDIN);
        my $head = $email->head();
        my $body = $email->body();
        $message = "";

        # Only use From: and Subject: hedaers
        my $subject = $head->get('subject');
        my $from = $head->get('from');
        $message .= "$subject From $from: ";
        $message .= join(" ",@$body);
        $message =~ s/\n//gm;   # Remove newlines
        $message =~ s/\s\s+/ /g;#  and collapse whitespace
}

if ($basename eq 'sendsms.pl' or $basename eq 'email2sms.pl') {
# sendsms
	open (LOG, ">>".$logfile) or *LOG = *STDERR;
	print LOG prettydate(); 
	print LOG " SMSsend: $HowIwasCalled\n"; 
	close LOG;

	unless ($hostaddress) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: hostaddress missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	unless ($number) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: number missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	unless ($user) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: username missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	unless ($pass) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: password missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}

	#my $msg = urlencode("@_");
	#$message =~ tr/\0-\xff//UC;		# unicode to latin-1

	if ($verbose) { print 'message to send     : ' . $message . "\n"; }
		# Split message into 160 character pieces up to $splitmax msgs
		my $offset = 0;
		$splitmax = 999 if ($splitmax == 0);
		while ($splitmax-- > 0 and length($message) > $offset) {
			my $messagepart = urlencode(substr(justASCII($message),$offset,160));
			$offset += 160;
			if ($verbose) { print 'short clean message : ' . $messagepart . "\n"; }
			my $document;
			if ($number =~ /^[0-9+-]+$/) {
			  $document = "/sendmsg?user=$user&passwd=$pass&cat=1&to=$number&text=$messagepart";
			} else {     # It isn't a number, assume it's in the address book
			  $document = "/sendmsg?user=$user&passwd=$pass&cat=1&ton=$number&text=$messagepart";
			}
		my $url = "http://$hostaddress" . $document;
		if ($verbose) { print 'SMSFinder URL       : ' . $url . "\n"; }
		
		#$ua->timeout($timeout);
		#my $response = get $url;
		my $response = httpGet($document);
		
		push (@msg, '"' . $messagepart . '" to ' . $number . ' via ' . $hostaddress); 
		if (defined $response) {
			if ($response =~ /ID: (\d+)/) {
				my $apimsgid = $1;
				if ($noma) {
					my $statuscode = -1;
					$document = "/querymsg?user=$user&passwd=$pass&apimsgid=$apimsgid";
					$url = "http://$hostaddress" . $document;
					if ($verbose) { print 'SMSFinder URL       : ' . $url . "\n"; }
					while (1) { # will be ended on timeout or success
						#$response = get $url;
						$response = httpGet($document);
						if (defined $response) {
							if ($response =~ /(Status|Err): (.+)/) {
								$statuscode = $2;
								if ($statuscode == 0) {
									# 0='Done'
									push (@msg, 'send successfully. MessageID: ' . $apimsgid); 
									$exitVal = 0; # set global ok
									last;
								} elsif ($statuscode == 2 or $statuscode == 3) {
									# 2='In progress'  3='Request Received'
									sleep 1;
									next;
								} elsif ($statuscode == 5) {
									# 5='Message ID Not Found'
									push (@msg, 'failed. With an very strange error: Message ID Not Found');
									$exitVal = 2; # set global critical
									last;
								} elsif ($statuscode == 1 or $statuscode == 4) {
									# 1='Done with error - message is not sent to all the recipients'
									# 4='Error occurred while sending the SMS from the SMSFinder'
									push (@msg, 'failed. Error: ' . $statuscode);
									$exitVal = 2; # set global critical
									last;
								} elsif ($1 eq 'Err') {
									push (@msg, join ('', ' failed. Error: ',
										(defined $smsErrorCodes{$statuscode}) ? $smsErrorCodes{$statuscode} : 'unknown' )); 
									$exitVal = 2; # set global critical
									last;
								} else {
									push (@msg, 'failed. With an unknown response: ' . $response);
									$exitVal = 2; # set global critical
									last;
								}
							}
						} else {
							push (@msg, 'unknown. Timeout or SMSFinder unreachable while querying result.');
							$exitVal = 2; # set global critical
							last;
						}
					}
				} else {
					# because Nagios notofication is blocking, we dont wait for message to be send.
					# not even until timeout
					push (@msg, 'queued successfully. MessageID: ' . $apimsgid); 
					$exitVal = 0; # set global ok
				}
			} elsif ($response =~ /Err: (\d+)/) {
				push (@msg, join(' ', ' failed. Error:',  (defined $smsErrorCodes{$1}) ? $smsErrorCodes{$1} : 'unknown' )); 
				$exitVal = 2; # set global critical
			} else {
				push (@msg, 'failed. With an unknown response: ' . $response);
				$exitVal = 2; # set global critical
			}
		} else {
				push (@msg, 'failed. Timeout or SMSFinder unreachable while try to send message.');
				$exitVal = 2; # set global critical
		}
		open (LOG, ">>".$logfile) or *LOG = *STDERR;
		print LOG prettydate(); 
		if (defined $contactgroup) { push (@msg, 'contactgroup: "' . "$contactgroup" . '"'); }
		print LOG ' SMSsend: ' . join(' ', @msg) . "\n"; 
		close LOG;
	}
	printResultAndExit ($exitVal, join(' ', @msg)); 
}

elsif ($basename eq 'smsreboot.pl') {
	unless ($hostaddress) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: hostaddress missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	unless ($user) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: username missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	unless ($pass) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: password missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	unless (httpPostLogin) {
		printResultAndExit (2, 'CRITICAL: SMS login failed!|');
	}
	unless (httpPostReboot) {
		httpGetLogout;
		printResultAndExit (1, 'WARNING: reboot failed!|');
	}
}

elsif ($basename eq 'check_smsfinder.pl') {
#check_smsfinder; 
	unless ($hostaddress) { 
		mypod2usage({
		-msg     => "\n" . 'ERROR: hostaddress missing!' . "\n",
		-verbose => 1,
		-exitval => 3 }); 
	}
	
	unless (httpPostLogin) {
		printResultAndExit (2, 'CRITICAL: SMS login failed!|');
	}
	my $response = httpGet('/statsSysinfo.html' . "?$loginID");
	my $response2 = httpGet('/statsSmsfinder.html' . "?$loginID" . "&1");
	httpGetLogout;
	
	my $firmware;
	
	if (defined $response) {
			if ($response =~ /200 OK.*?Product Model Number.*?>(\S+?)<.*Firmware Version.*?>(\S+?)<.*?>/) {
			push (@msg, "model: $1", "firmware: $2");
			$firmware = $2;
			} else {
			printResultAndExit (2, "CRITICAL: $hostaddress SMSFinder returned bad response. \n" . $response); 
			}
	}
	
	if ($firmware >= 1.44) {
		if (defined $response2) {
			if ($response2 =~ /200 OK.*?Signal Strength.*?>\S+?([0-9]+).*?/)	{
					my $strength = $1;
					if ($strength > 0) {	
						$strength = sprintf("%.1f",($strength * 100) / 31);
						push (@perfdata, "strength=$strength\%;$warning;$critical;;");	
						push (@msg, "GSM signal strength is $strength\%");
						if ($strength < $critical){
							printResultAndExit (2, 'CRITICAL: ' . join(' - ', @msg) . '|' . join (' ', @perfdata));
						} elsif ($strength < $warning){
							printResultAndExit (1, 'WARNING: ' . join(' - ', @msg) . '|' . join (' ', @perfdata));
						} else {
							printResultAndExit (0, 'OK: ' . join(' - ', @msg) . '|' . join (' ', @perfdata));
						}
					}
					else
					{
						push (@msg, "No GSM signal, maybe not connected to the Network.");
						$exitVal = 2;
						printResultAndExit ($exitVal, 'CRITICAL: ' . join(' - ', @msg) . '|' . join (' ', @perfdata)); 
					} 
			} else {
			printResultAndExit (2, "CRITICAL: $hostaddress SMSFinder returned bad response. \n" . $response2); 
			}
		} else {
		printResultAndExit (2, 'CRITICAL: no response from ' . $hostaddress . ' within ' . $timeout . ' seconds.'); 
		}
	} else {
		if (defined $response) {
			if ($response =~ /200 OK.*?Product Model Number.*?>(\S+?)<.*Firmware Version.*?>(\S+?)<.*MAC Address.*?Signal Strength\s*<.*?>(\d+)\s*<.*?Live Details/) {
				my $strength = $3;
				if ($strength > 0) {	
					$strength = sprintf("%.1f",($strength * 100) / 31);
					push (@perfdata, "strength=$strength\%;$warning;$critical;;");	
					push (@msg, "GSM signal strength is $strength\%");
					if ($strength < $critical){
						$exitVal = 2;
					} elsif ($strength < $warning){
						$exitVal = 1;
					} else {
						$exitVal = 0;
					}
					push (@msg, "model: $1", "firmware: $2");
					printResultAndExit ($exitVal, $state[$exitVal].': ' . join(' - ', @msg) . '|' . join (' ', @perfdata)); 
				}else {
					push (@msg, "No GSM signal, maybe not connected to the Network.");
					push (@msg, "model: $1", "firmware: $2");
					printResultAndExit (2, 'CRITICAL: ' . join(' - ', @msg) . '|' . join (' ', @perfdata)); 
				}
			} else {
				printResultAndExit (2, "CRITICAL: $hostaddress SMSFinder returned bad response. \n" . $response); 
			}
		}
	}
}

elsif ($basename eq 'smsack.cgi') {
	my $postdata;
	read(STDIN, $postdata, $ENV{'CONTENT_LENGTH'}) 
		if (defined $ENV{'CONTENT_LENGTH'}) or read(STDIN, $postdata, 1000);
	
	# extract the XML data
	$postdata =~ s/^.*\&//;
	$postdata =~ s/^XMLDATA=//;


	$postdata = urldecode($postdata); 
	$postdata =~ s/\012/ /g;
	$postdata =~ s/\015/ /g;
	
	if (defined $ENV{'HTTP_USER_AGENT'}) {
		open (LOG, ">>".$logfile) or *LOG = *STDERR;
	} else {
		*LOG = *STDERR;
	}

	print LOG prettydate() . ' SMSreceived: ' . $postdata . "\n"; 


	httpDie ('Invalid XML Format') unless ($postdata =~ m/^\s*<\?xml .*>/i);

	my $xml = XMLin($postdata);

	my $SenderNumber = $xml->{'SenderNumber'};
	my $Message = $xml->{'Message'};
	my $received = $xml->{'Date'}.' '.$xml->{'Time'};


	my $status;
	
	my $host = '';
	my $service = '';
	my $alerttype = 'HostAlert';
	
	# check new status 
	if ($Message =~ m/$ok/) {
		$status="OK";
	} elsif ($Message =~ m/$ack/) {
		$status="ACK";
	}
	
	# get service/host
	# if ($Message =~ m{ (HostAlert|ServiceAlert) (\S+)\[[\]*]\]/(.+) is })
	if ($Message =~ m{^\s*\S+\s+\S+\s+([^,>]+),*([^>]*)>})
	{
		$alerttype = 'ServiceAlert' if $2 ne '';
		$host      = $1;
		$service   = $2;
	}
	#'$NOTIFICATIONTYPE$ $HOSTNAME$> is $HOSTSTATE$ /$SHORTDATETIME$/ $OUTPUT$'
	#'$NOTIFICATIONTYPE$ $HOSTNAME$,$SERVICEDESC$> is $SERVICESTATE$ /$SHORTDATETIME$/ $SERVICEOUTPUT$'

	
	# contact is verified via the nagios object.cache
	my $verified_contact=0;
	open (NCFG,"<".$object_cache);
	while (<NCFG>) {
		if (/pager\s+(\+?\d+)/) {
			if($SenderNumber eq $1) {
				# pager is ok
				$verified_contact=1;
				last;
			} 
		}
	}
	close(NCFG);
		
	if($verified_contact or $do_not_verify) {
		my $comment = " by $SenderNumber at $received $Message";
		
		if ($status eq "OK") {
			# set service OK
			$comment = "Reset" . $comment;
			open (CMD, ">>" . $nagios_cmd);
			if ($alerttype eq 'ServiceAlert') {
				print CMD "[".time()."] PROCESS_SERVICE_CHECK_RESULT;".$host.";".$service.";0;".$comment."\n";
				print CMD "[".time()."] ENABLE_SVC_NOTIFICATIONS;".$host.";".$service."\n";
			} else {
				print CMD "[".time()."] PROCESS_HOST_CHECK_RESULT;".$host.";0;".$comment."\n";
				print CMD "[".time()."] ENABLE_HOST_NOTIFICATIONS;".$host."\n";
			}
			close (CMD);
		} elsif ($status eq "ACK") {
			# aknowledge service
			$comment="Aknowledged".$comment;
			open (CMD, ">>" . $nagios_cmd);
			if ($alerttype eq 'ServiceAlert') {
				print CMD "[".time()."] ACKNOWLEDGE_SVC_PROBLEM;".$host.";".$service.";1;1;1;".$SenderNumber.";".$comment."\n";
			} else {
				print CMD "[".time()."] ACKNOWLEDGE_HOST_PROBLEM;".$host.";1;1;1;".$SenderNumber.";".$comment."\n";
			}
			close (CMD);
		} 
		print LOG prettydate() . ' SMS sender verified\n';
		httpOutput('ACCEPTED'); 
	} else {
		print LOG prettydate() . ' SMS sender not verified\n';
		httpOutput('NOT ACCEPTED'); 
	}
	print LOG " From=$SenderNumber Received=$received Status=$status Host=$host Service=$service MSG=\"$Message\"\n";
	close LOG;
}

else {
	mypod2usage({
		-verbose	=> 1,
		-exitval	=> 3
	});
}


# DOCUMENTATION

=head1 NAME

=over 1

=item B<smsfinder.pl>

	the Nagios 
	- check plugin, 
	- notification handler / sendSMS and 
	- ACKnowledgement addon / CGI handler
	for the MultitechSMSFinder

=back

=head1 DESCRIPTION

=over 1

=item Depending on how it is called,

	- Checks a Multitech SMSFinder and returns if it is connected 
		to the GSM Network and the level of signal strength.
	- send an SMS via a Multitech SMSFinder
	- handles a received SMS and sets the ACKnowledgement in Nagios

	*THIS script should be symlinked/copied according to your needs*
	If you symlink it, make the CGI handler the original. Your http
	server may not accept symlinked CGIs.
	So once more - this script is all three in one.


=back

=head1 SYNOPSIS

=over 1

=item B<check_smsfinder.pl>

	-H hostaddress
	[-t|--timeout=<timeout in seconds>]
	[-v|--verbose]
	[-h|--help] [-V|--version]
	[-u|--user=<user>]
	[-p|--password=<password>]

=item B<sendsms.pl>

	-H hostaddress
	[-t|--timeout=<timeout in seconds>]
	[-v|--verbose]
	[-h|--help] [-V|--version]
	[-u|--user=<user>]
	[-p|--password=<password>]
	[-s|--splitmax=<number>]
	-n|--number=<telephone number of the recipient>
	-m|--message=<message text>

=item B<smsack.cgi>

	[-v|--verbose]
	[-h|--help] [-V|--version]
	CONTENT_LENGTH via ENVIRONMENT
	SMS data       via STDIN (from http post)

=item B<email2sms.pl>

	-H hostaddress
	[-t|--timeout=<timeout in seconds>]
	[-v|--verbose]
	[-h|--help] [-V|--version]
	[-u|--user=<user>]
	[-p|--password=<password>]
	[-s|--splitmax=<number>]
	-n|--number=<telephone number of the recipient>
	-m|--message=<message text>

=back

=head1 OPTIONS

=over 4

=item -H <hostaddress>

Hostaddress of the SMSFinder

=item -t|--timeout=<timeout in seconds>

Time in seconds to wait before script stops.

=item -v|--verbose

Enable verbose mode and show whats going on.

=item -V|--version

Print version an exit.

=item -h|--help

Print help message and exit.

=item -n|--number

Telephone number of the SMS recipient

=item -m|--message

SMS message text

=item -w|--warning

Warning level for signal strength in procent. (Default = 40)

=item -c|--critical

Critical level for signal strength in procent. (Default = 20)

=item --noma

NoMa switch - try to check if the send SMS is send, not just queued.

=back


=head1 HOWTO integrate with Nagios

=over 1

=item *

Prepare your system as described below, be well informed and (sort of) 
remote control your Nagios via your mobile and a Multitech SMSFinder.

=back

=head2 How to reset/overrule a host/service state?

Just prepend the notification SMS with "OK " and send it back to your SMSFinder. 

The host/service state will be set to OK and notifications enabled again.


=head2 How to acknowledge a notified outage?

Just prepend the notification SMS with "ACK " and send it back to your SMSFinder. 

The host/service state will be acknowledged and notifications disabled 
until the host/service is fine again.


=head2 How to prepare your system for acknowledgements?

1. Configure the SMSFinder to use the HTTP API to send and receive SMS.

Configure the following in the web interface of your SMSFinder:

1.a. Access for your Nagios server(s) on the 
"Administration > Admin Access > Allowed Networks" page.

1.b. define a SMS user on the 
"SMS Services > Send SMS Users" page.

1.c. switch on the HTTP send API on the 
"SMS Services > SMS API > Send API" page.

1.d. configure the HTTP receive API on the
"SMS Services > SMS API > Receive API" page.
(do not enable authentication, and be sure to set the POST Interval to 0)

2. Configure the notifications (via sendsms.pl) in Nagios as shown in the examples.

3. Configure the web server to handle the acknowledgements (via smsack.cgi).

4. Alter the paths in the congfig section on top of this script to match your system setup.

5. Ensure that the logfile is writable by the Nagios and the web server user:
 chown nagios:www-data /usr/local/nagios/var/smsfinder.log && chmod 664 /usr/local/nagios/var/smsfinder.log

6. Add the passwords to the /usr/local/nagios/etc/resource.cfg
 $USER13$=smsuser
 $USER14$=smspass
 $USER15$=adminuser
 $USER16$=adminpass


=head1 EXAMPLE for Nagios check configuration 

 # command definition to check SMSFinder via HTTP
 define command {
	command_name		check_smsfinder
	command_line		$USER1$/check_smsfinder.pl -H $HOSTADDRESS$ -u $USER15$ -p $USER16$ -w $ARG1$ -c $ARG2$
 }

 # service definition to check the SMSFinder
 define service {
	use					generic-service
	host_name			smsfinder
	service_description	smsfinder
	check_command		check_smsfinder!40!20	 # warning and critical in percent
	## maybe it's whise to alter the service/host template
	#contact_groups		smsfinders
  }
 
=head1 EXAMPLE for Nagios notification configuration 

 define command {
	command_name    notify-host-by-sms
	command_line    /usr/local/nagios/smsack/sendsms.pl -H $HOSTADDRESS:smsfinder$ -u $USER13$ -p $USER14$ -n $CONTACTPAGER$ -m '$NOTIFICATIONTYPE$ $HOSTNAME$ is $HOSTSTATE$ /$SHORTDATETIME$/ $HOSTOUTPUT$'
 }

 define command {
	command_name    notify-service-by-sms
	command_line    /usr/local/nagios/smsack/sendsms.pl -H $HOSTADDRESS:smsfinder$ -u $USER13$ -p $USER14$ -n $CONTACTPAGER$ -m '$NOTIFICATIONTYPE$ $HOSTNAME$,$SERVICEDESC$ is $SERVICESTATE$ /$SHORTDATETIME$/ $SERVICEOUTPUT$'
 }

 # contact definition - maybe it's wise to alter the contact template
 define contact {
	contact_name                    smsfinder
	use                             generic-contact
	alias                           SMS Nagios Admin
	# send notifications via email and SMS
	service_notification_commands   notify-service-by-email,notify-service-by-sms
	host_notification_commands      notify-host-by-email,notify-host-by-sms
	email                           nagios@localhost
	pager                           +491725555555		# alter this please!
 }

 # contact definition - maybe it's wise to alter the contact template
 define contactgroup {
	contactgroup_name       smsfinders
	alias                   SMS Nagios Administrators
	members                 smsfinder
 }

=head1 EXAMPLE for Apache configuraion

 ScriptAlias /nagios/smsack "/usr/local/nagios/smsack"
 <Directory "/usr/local/nagios/smsack">
 	Options ExecCGI
 	AllowOverride None
 	Order allow,deny
 	Allow from 10.0.10.57	# SMS Finder
 </Directory>

=cut



# vim: ts=4 shiftwidth=4 softtabstop=4 
#backspace=indent,eol,start expandtab
