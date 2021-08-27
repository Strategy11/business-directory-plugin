<?php

function _x( $string ) {
	return $string; }
function absint( $number ) {
	return intval( $number ); }
function esc_attr() {
	return array_shift( func_get_args() ); }
function esc_html() {
	return array_shift( func_get_args() ); }
function esc_url() {
	return array_shift( func_get_args() ); }
function get_the_title() {
	return 'Test Title'; }
function get_permalink() {
	return 'https://example.org'; }
function add_query_arg() {
	return array_pop( func_get_args() ); }
function admin_url() {
	return 'https://exmaple.org/wp-admin'; }
// function get_term_children() {}
// function plugin_dir_path() {}
