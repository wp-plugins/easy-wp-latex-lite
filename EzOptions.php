<?php

/*
  Copyright (C) 2008 www.ads-ez.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or (at
  your option) any later version.

  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists("EzBaseOption")) {

  class EzBaseOption { // base EzOption class

    var $name, $desc, $title, $tipTitle, $tipWidth, $tipWarning, $value, $type;
    var $width, $labelWidth, $height, $before, $between, $after, $style;

    function EzBaseOption($type, $name) {
      $vars = get_object_vars($this);
      foreach ($vars as $k => $v) {
        $this->$k = '';
      }
      $this->type = $type;
      $this->name = $name;
      $this->tipWidth = '240';
    }

    function __clone() {
      foreach ($this as $key => $val) {
        if (is_object($val) || (is_array($val))) {
          $this->{$key} = unserialize(serialize($val));
        }
      }
    }

    function get() {
      return $this->value;
    }

    function set($properties, $desc = '') {
      if (!isset($properties)) {
        return;
      }
      if (is_array($properties)) {
        foreach ($properties as $k => $v) {
          $key = strtolower($k);
          if (floatval(phpversion()) > 5.3) {
            if (property_exists($this, $key)) {
              $this->$key = $v;
            }
          }
          else {
            if (array_key_exists($key, $this)) {
              $this->$key = $v;
            }
          }
        }
      }
      else {
        $this->value = $properties;
        if (!empty($desc)) {
          $this->desc = $desc;
        }
      }
    }

    function preRender() {
      $toolTip = $this->mkToolTip();
      if (!empty($this->labelWidth)) {
        $style = "style='display:inline-block;width:{$this->labelWidth}'";
      }
      else {
        $style = "";
      }
      echo "{$this->before}\n<label $style for='{$this->name}' $toolTip>\n";
    }

    function postRender() {
      echo "</label>\n{$this->after}\n";
    }

    function render() {
      $this->preRender();
      echo "<input type='{$this->type}' id='{$this->name}' name='{$this->name}'";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }
      echo " value='{$this->value}' />{$this->desc}\n";
      $this->postRender();
    }

    function updateValue() {
      if (isset($_POST[$this->name])) {
        $this->value = $_POST[$this->name];
      }
    }

    function mkToolTip() {
      if (!empty($this->title)) {
        if (!empty($this->tipWarning)) {
          if (empty($this->tipTitle)) {
            $this->tipTitle = "Warning!";
          }
          $warning = ", BGCOLOR, '#ffcccc', FONTCOLOR, '#800000'"
                  . ", BORDERCOLOR, '#c00000'";
        }
        else {
          $warning = '';
        }
        $toolTip = "onmouseover=\"Tip('" . htmlspecialchars($this->title)
                . "', WIDTH, {$this->tipWidth}, TITLE, '{$this->tipTitle}'"
                . $warning . ", FIX, [this, 5, 5])\" onmouseout=\"UnTip()\"";
      }
      else {
        $toolTip = "";
      }
      return $toolTip;
    }

    function mkTagToTip() {
      if (!empty($this->title)) {
        $toolTip = "<span style='text-decoration:underline' "
                . "onmouseover=\"Tip('{$this->title}')\" "
                . "onclick=\"TagToTip('{$this->name}', WIDTH, 300, "
                . "TITLE, '{$this->tipTitle}', STICKY, 1, CLOSEBTN, true, "
                . "CLICKCLOSE, true, FIX, [this, 15, 5])\" "
                . "onmouseout=\"UnTip();\">{$this->desc}</span>";
      }
      else {
        $toolTip = "";
      }
      return $toolTip;
    }

    static function getValues($ezOptions) {
      $options = array();
      foreach ($ezOptions as $k => $o) {
        $options[$k] = $o->get();
      }
      return $options;
    }

    static function setValues($options, &$ezOptions) {
      $error = '';
      foreach ($options as $k => $v) {
        if (isset($ezOptions[$k])) {
          if (!empty($_POST)) {
            // Suppress errors because $_POST won't be set for checkboxes and flags
            @$value = $_POST[$k];
          }
          else {
            $value = $v;
          }
          $ezOptions[$k]->set($value);
        }
        else {
          $error .= "Cannot find <code>ezOptions[$k]</code><br />";
        }
      }
      return $error;
    }

  }

  class EzCheckBox extends EzBaseOption {

    function EzCheckBox($name) {
      parent::EzBaseOption('checkbox', $name);
    }

    function render() {
      $this->preRender();
      echo "<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}' ";
      }
      if ($this->value) {
        echo "checked='checked' ";
      }
      echo "/>{$this->desc}\n";
      $this->postRender();
    }

    function updateValue() {
      $this->value = isset($_POST[$this->name]);
    }

  }

  class EzRadioBox extends EzBaseOption { // Radiobox

    var $choices;

    function EzRadioBox($name) {
      parent::EzBaseOption('radio', $name);
    }

    function &addChoice($name, $value, $desc) {
      $subname = $this->name . '_' . $name;
      $this->choices[$subname] = new EzBaseOption('radio', $subname);
      $this->choices[$subname]->value = $value;
      $this->choices[$subname]->desc = $desc;
      return $this->choices[$subname];
    }

    function preRender() {
      $toolTip = $this->mkToolTip();
      if (!empty($this->labelWidth)) {
        $style = "style='display:inline-block;width:{$this->labelWidth}'";
      }
      else {
        $style = "";
      }
      echo "{$this->before}\n";
      echo "<span $style id='{$this->name}' $toolTip>{$this->desc} {$this->between}</span>";
      echo "\n{$this->after}\n";
    }

    function postRender() {

    }

    function render() {
      $this->preRender();
      if (!empty($this->choices)) {
        foreach ($this->choices as $k => $v) {
          echo $v->before, "\n";
          echo "<label for='{$k}'>\n";
          echo "<input type='{$v->type}' id='{$k}' name='{$this->name}' ";
          if ($this->value == $v->value) {
            echo "checked='checked'";
          }
          echo " value='{$v->value}' /> {$v->desc}\n</label>\n{$v->after}\n";
        }
      }
      $this->postRender();
    }

  }

  class EzSelect extends EzBaseOption { // Drop-down menu.

    var $choices = array();

    function EzSelect($name) {
      parent::EzBaseOption('select', $name);
    }

    function &addChoice($name, $value = '', $desc = '') {
      $subname = $this->name . '_' . $name;
      if (is_array($this->choices) && array_key_exists($subname, $this->choices)) {
        die("Fatal Error [addChoice]: New Choice $subname already exists "
                . "in {$this->name}");
      }
      $this->choices[$subname] = new EzBaseOption('choice', $subname);
      $this->choices[$subname]->value = $value;
      $this->choices[$subname]->desc = $desc;
      return $this->choices[$subname];
    }

    function render() {
      $this->preRender();
      echo "{$this->desc} {$this->between}\n"
      . "<select id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }

      echo '>';
      if (!empty($this->choices)) {
        foreach ($this->choices as $k => $v) {
          echo "{$v->before}<option value='{$v->value}' ";
          if ($this->value == $v->value) {
            echo "selected='selected'";
          }
          echo ">{$v->desc}</option>{$v->after}\n";
        }
      }
      echo "</select>\n";
      $this->postRender();
    }

  }

  class EzMessage extends EzBaseOption { // Not an option, but a Message in the admin panel

    function EzMessage($name) { // constructor
      parent::EzBaseOption('', $name);
    }

    function render() {
      $this->preRender();
      if (!empty($this->value)) {
        echo $this->value, "\n";
      }
      if (!empty($this->desc)) {
        echo $this->desc, "\n";
      }
      $this->postRender();
    }

  }

  class EzHelpTag extends EzBaseOption { // Not an option, but to render help text

    function EzHelpTag($name) { // constructor
      parent::EzBaseOption('', $name);
    }

    function render() {
      $toolTip = $this->mkTagToTip();
      echo "{$this->before}\n";
      echo "$toolTip\n";
      echo "{$this->after}\n";
    }

  }

  class EzHelpPopUp extends EzBaseOption { // Not an option, but to popup a url

    function EzHelpPopUp($name) { // constructor
      parent::EzBaseOption('', $name);
    }

    function render() {
      echo "{$this->before}\n";
      echo "<span style='text-decoration:underline' "
      . "onmouseover=\"Tip('{$this->title}')\" "
      . "onclick=\"popupwindow('{$this->name}', 'DontCare', 1024, 1024);"
      . "return false;\" onmouseout=\"UnTip();\">"
      . "{$this->desc}</span>\n";
      echo "{$this->after}\n";
    }

  }

  class EzTextArea extends EzBaseOption {

    function EzTextArea($name) {
      parent::EzBaseOption('textarea', $name);
      $this->width = 50;
      $this->height = 5;
      $this->style = "width: 96%; height: 180px;";
    }

    function render() {
      $this->preRender();
      echo "{$this->desc}<textarea cols='{$this->width}' rows='{$this->height}'"
      . " name='{$this->name}' id='{$this->name}' style='{$this->style}'>",
      stripslashes(htmlspecialchars($this->value)),
      "</textarea>\n";
      $this->postRender();
    }

  }

  class EzText extends EzBaseOption {

    function EzText($name) {
      parent::EzBaseOption('text', $name);
    }

    function render() {
      $this->preRender();
      echo "{$this->desc}{$this->between}"
      . "<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }
      echo " value='{$this->value}' />\n";
      $this->postRender();
    }

  }

  class EzSubmit extends EzBaseOption {

    function EzSubmit($name) {
      parent::EzBaseOption('submit', $name);
      $this->value = $this->desc;
    }

    function render() {
      $this->preRender();
      echo "<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }
      echo " value='{$this->desc}' />\n";
      $this->postRender();
    }

  }

  class EzColorPicker extends EzBaseOption { // ColorPickers

    function EzColorPicker($name) {
      parent::EzBaseOption('text', $name);
      $this->style = "border:0px solid;";
    }

    function render() {
      $this->preRender();
      echo $this->desc;
      echo "$this->between\n";
      echo "&nbsp;<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }

      echo " class=\"color {hash:false,caps:true,pickerFaceColor:'transparent',pickerFace:3,pickerBorder:0,pickerInsetColor:'black'}\"";
      echo " value='{$this->value}' />\n";
      $this->postRender();
    }

  }

  class EzOneTab extends EzBaseOption { // a tab in the mini-tab container, miniTab

    var $mTabOptions;

    function EzOneTab($name) {
      parent::EzBaseOption('onetab', $name);
      $this->mTabOptions = array();
    }

    function &addTabOption($type, $key) {
      $subname = $this->name . '_' . $key;
      if (is_array($this->mTabOptions) && array_key_exists($subname, $this->mTabOptions)) {
        die("Fatal Error [addTabOption]: New Option $subname already exists in {$this->name}");
      }
      if (class_exists($type)) { // Specialized class for this type of input
        $this->mTabOptions[$key] = new $type($subname);
      }
      else {
        $this->mTabOptions[$key] = new EzBaseOption($type, $subname);
      }
      return $this->mTabOptions[$key];
    }

    function render() {
      $toolTip = $this->mkToolTip();
      echo "{$this->before}\n";
      if (!empty($this->mTabOptions)) {
        foreach ($this->mTabOptions as $k => $o) {
          if (!empty($o)) {
            $o->render();
          }
        }
      }
      echo "{$this->after}\n";
    }

    function updateValue() {
      foreach ($this->mTabOptions as $option) {
        $option->updateValue();
      }
    }

  }

  class EzMiniTab extends EzBaseOption { // a mini-tab container.

    var $tabs;

    function EzMiniTab($name) {
      parent::EzBaseOption('minitab', $name);
      $this->tabs = array();
    }

    function &addTab($name) {
      $subname = $this->name . '-' . $name;
      if (array_key_exists($subname, $this->tabs)) {
        die("Fatal Error [addTab]: New Tab $subname already exists in {$this->name}");
      }
      $this->tabs[$subname] = new EzOneTab($subname);
      return $this->tabs[$subname];
    }

    function render() {
      $toolTip = $this->mkToolTip();
      echo "{$this->before}\n";
      echo "<div><ul class='tabs' name='tabs' id='{$this->name}_MiniTabs'>\n";
      $class = "class='current'";
      foreach ($this->tabs as $tab) {
        echo "<li><a href='#' $class id='mtab_{$tab->name}_link'>{$tab->desc}</a></li>\n";
        $class = '';
      }
      echo "</ul>\n</div><!-- of ul tabs -->\n";

      $current = '_current';
      foreach ($this->tabs as $tab) {
        $name = $tab->name;
        echo "<div class='tab$current' id='mtab_$name'>\n";
        $tab->render();
        echo "</div><!-- End: $name --> \n";
        $current = '';
      }
      echo "{$this->after}\n";
    }

    function updateValue() {
      foreach ($this->tabs as $tab) {
        $tab->updateValue();
      }
    }

  }

}

if (!class_exists("EzBasePlugin")) {

  class EzBasePlugin {

    var $slug, $domain, $name, $plgDir, $plgURL, $plgFile;
    var $ezTran, $ezAdmin, $myPlugins;
    var $isPro, $strPro;
    var $options;

    function __construct($slug, $name, $file) {
      $this->slug = $slug;
      $this->plgDir = dirname($file);
      $this->plgURL = plugin_dir_url($file);
      $this->plgFile = $file;
      $this->name = $name;
      $this->isPro = is_dir("{$this->plgDir}/pro") && file_exists("{$this->plgDir}/pro/pro.php");
      $this->strPro = ' Lite';
      if ($this->isPro) {
        $this->strPro = ' Pro';
      }
      if (is_admin()) {
        require_once($this->plgDir . '/EzTran.php');
        if ($this->slug == "easy-adsense") {
          $this->domain = "easy-adsenser";
        }
        else {
          $this->domain = $this->slug;
        }
        $this->ezTran = new EzTran($file, "{$name}{$this->strPro}", $this->domain);
        $this->ezTran->setLang();
      }
    }

    function __destruct() {

    }

    function EzBasePlugin($slug, $name, $file) {
      if (version_compare(PHP_VERSION, "5.0.0", "<")) {
        $this->__construct($slug, $name, $file);
        register_shutdown_function(array($this, "__destruct"));
      }
    }

    function handleSubmits() {
      if (empty($_POST)) {
        return;
      }
    }

    function printAdminPage() {
      // if translating, print translation interface
      if ($this->ezTran->printAdminPage()) {
        return;
      }
      $this->handleSubmits();
      require_once($this->plgDir . '/myPlugins.php');
      $slug = $this->slug;
      $plg = $this->myPlugins[$slug];
      $plgURL = $this->plgURL;
      if ($this->isPro || file_exists($this->plgDir . '/EzAdminPro.php')) {
        require_once($this->plgDir . '/EzAdminPro.php');
        $this->ezAdmin = new EzAdminPro($plg, $slug, $plgURL);
      }
      else {
        require_once($this->plgDir . '/EzAdmin.php');
        $this->ezAdmin = new EzAdmin($plg, $slug, $plgURL);
      }
      if ($this->options['kill_author']) {
        $this->ezAdmin->killAuthor = true;
      }
      $this->ezAdmin->domain = $this->domain;
      $this->ezAdmin->plgFile = $this->plgFile;
      return $this->ezAdmin;
    }

  }

}
