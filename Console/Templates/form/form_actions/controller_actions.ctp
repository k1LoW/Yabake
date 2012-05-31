    /**
     * <?php echo $admin ?>form_front method
     *
     * @return void
     */
     public function <?php echo $admin ?>form_front() {
     
         $this->Transition->checkData('<?php echo $admin ?>form_confirm');
<?php
  foreach (array('belongsTo', 'hasAndBelongsToMany') as $assoc):
      foreach ($modelObj->{$assoc} as $associationName => $relation):
          if (!empty($associationName)):
              $otherModelName = $this->_modelName($associationName);
              $otherPluralName = $this->_pluralName($associationName);
              echo "        \${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
              $compact[] = "'{$otherPluralName}'";
          endif;
      endforeach;
  endforeach;
  if (!empty($compact)):
      echo "        \$this->set(compact(".join(', ', $compact)."));\n";
  endif;
?>
    }

<?php $compact = array(); ?>
    /**
    * <?php echo $admin ?>form_confirm method
    *
    * @return void
    */
    public function <?php echo $admin ?>form_confirm() {
    
        $this->Transition->automate(
            '<?php echo $admin ?>form_front', // prev
            '<?php echo $admin ?>form_save' // next
        );
        $mergedData = $this->Transition->mergedData();
<?php $compact[] = "'mergedData'"; ?>
<?php
  foreach (array('belongsTo', 'hasAndBelongsToMany') as $assoc):
      foreach ($modelObj->{$assoc} as $associationName => $relation):
          if (!empty($associationName)):
              $otherModelName = $this->_modelName($associationName);
              $otherPluralName = $this->_pluralName($associationName);
              echo "        \${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
              $compact[] = "'{$otherPluralName}'";
          endif;
      endforeach;
  endforeach;
  if (!empty($compact)):
      echo "    \$this->set(compact(".join(', ', $compact)."));\n";
  endif;
?>
    }

<?php $compact = array(); ?>
    /**
    * <?php echo $admin ?>form_save method
    *
    * @return void
    */
    public function <?php echo $admin ?>form_save() {
    
        $this->Transition->checkPrev(array(
            '<?php echo $admin ?>form_front',
            '<?php echo $admin ?>form_confirm',
        ));        
        $this-><?php echo $currentModelName; ?>->create();
        $this-><?php echo $currentModelName; ?>->begin();
        if ($this-><?php echo $currentModelName; ?>->save($this->Transition->mergedData())) {
            $this-><?php echo $currentModelName; ?>->commit();
            $this->Transition->clearData();
            $this->Transition->redirect('<?php echo $admin ?>form_complete');
            $this->Session->setFlash(__('The <?php echo strtolower($singularHumanName); ?> has been saved'));        
        } else {
            $this-><?php echo $currentModelName; ?>->rollback();
            $this->Session->setFlash(__('The <?php echo strtolower($singularHumanName); ?> could not be saved. Please, try again.'));
            $this->redirect(array('action' => '<?php echo $admin ?>form_front'));
        }
    }
    
    /**
    * <?php echo $admin ?>form_complete method
    *
    * @return void
    */
    public function <?php echo $admin ?>form_complete() {
        $this->Transition->checkPrev(array(
            '<?php echo $admin ?>form_save',
        ));
    }

