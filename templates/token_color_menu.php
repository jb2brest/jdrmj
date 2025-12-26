<!-- Menu contextuel pour changer la couleur des pions -->
<?php if (isset($canEdit) && $canEdit): ?>
<div id="tokenColorMenu" class="token-color-menu" style="display: none; position: absolute; z-index: 10000; background: white; border: 1px solid #ccc; border-radius: 8px; padding: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <div class="mb-2">
        <strong style="font-size: 12px; color: #666;">Couleur du pion</strong>
    </div>
    <div class="color-palette mb-2" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px;">
        <button class="color-option" data-color="#007bff" style="width: 30px; height: 30px; border: 2px solid #007bff; border-radius: 4px; background: #007bff; cursor: pointer;" title="Bleu"></button>
        <button class="color-option" data-color="#28a745" style="width: 30px; height: 30px; border: 2px solid #28a745; border-radius: 4px; background: #28a745; cursor: pointer;" title="Vert"></button>
        <button class="color-option" data-color="#dc3545" style="width: 30px; height: 30px; border: 2px solid #dc3545; border-radius: 4px; background: #dc3545; cursor: pointer;" title="Rouge"></button>
        <button class="color-option" data-color="#ffc107" style="width: 30px; height: 30px; border: 2px solid #ffc107; border-radius: 4px; background: #ffc107; cursor: pointer;" title="Jaune"></button>
        <button class="color-option" data-color="#FF8C00" style="width: 30px; height: 30px; border: 2px solid #FF8C00; border-radius: 4px; background: #FF8C00; cursor: pointer;" title="Orange"></button>
        <button class="color-option" data-color="#6f42c1" style="width: 30px; height: 30px; border: 2px solid #6f42c1; border-radius: 4px; background: #6f42c1; cursor: pointer;" title="Violet"></button>
        <button class="color-option" data-color="#20c997" style="width: 30px; height: 30px; border: 2px solid #20c997; border-radius: 4px; background: #20c997; cursor: pointer;" title="Cyan"></button>
        <button class="color-option" data-color="#fd7e14" style="width: 30px; height: 30px; border: 2px solid #fd7e14; border-radius: 4px; background: #fd7e14; cursor: pointer;" title="Orange foncé"></button>
        <button class="color-option" data-color="#e83e8c" style="width: 30px; height: 30px; border: 2px solid #e83e8c; border-radius: 4px; background: #e83e8c; cursor: pointer;" title="Rose"></button>
        <button class="color-option" data-color="#6c757d" style="width: 30px; height: 30px; border: 2px solid #6c757d; border-radius: 4px; background: #6c757d; cursor: pointer;" title="Gris"></button>
        <button class="color-option" data-color="#343a40" style="width: 30px; height: 30px; border: 2px solid #343a40; border-radius: 4px; background: #343a40; cursor: pointer;" title="Noir"></button>
        <button class="color-option" data-color="#ffffff" style="width: 30px; height: 30px; border: 2px solid #000; border-radius: 4px; background: #ffffff; cursor: pointer;" title="Blanc"></button>
    </div>
    <div class="mt-2">
        <label style="font-size: 11px; color: #666;">Personnalisée:</label>
        <input type="color" id="customColorPicker" style="width: 100%; height: 30px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">
    </div>
</div>
<?php else: ?>
<!-- Menu contextuel non affiché car canEdit = <?php echo isset($canEdit) ? ($canEdit ? 'true' : 'false') : 'not set'; ?> -->
<?php endif; ?>
