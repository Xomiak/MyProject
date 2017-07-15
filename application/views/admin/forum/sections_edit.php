<?php
include("application/views/admin/header.php");
?>
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="200px" bgcolor="#DDDDDD" valign="top"><?php include("application/views/admin/menu.php"); ?></td>
        <td valign="top">
            <h1><?=$title?></h1>
            <table width="100%" cellpadding="0" cellspacing="0" height="20px" bgcolor="#DDDDDD">
                <tr>
                    <td width="11px" height="20px"><img src="/img/left-hmenu.jpg" /></td>
                    <td><a href="/admin/forum/sections/">Разделы</a></td>
                    <td><a href="/admin/forum/sections/add/">Добавить раздел</a></td>
                    <td><a href="/admin/forum/topics/">Темы</a></td>
                    <td><a href="/admin/forum/topics/add/">Добавить тему</a></td>
                    <td><a href="/admin/forum/messages/">Сообщения</a></td>
                    <td><a href="/admin/forum/options/">Настройки</a></td>
                    <td><a href="/admin/forum/options/add/">Добавить опцию</a></td>
                </tr>
            </table>
            <br />
        
            <form enctype="multipart/form-data" action="<?=$_SERVER['REQUEST_URI']?>" method="post">
                <table>
                    <tr>
                        <td>Название *:</td>
                        <td><input required placeholder="Название" maxlength="1000" type="text" name="name" size="50" value="<?php if(isset($_POST['name'])) echo $_POST['name']; else echo $section['name']?>" /></td>
                    </tr>
                    <tr>
                        <td>url:</td>
                        <td><input type="text" name="url" size="50" value="<?php if(isset($_POST['url'])) echo $_POST['url']; else echo $section['url']?>" /></td>
                    </tr>
                    <tr>
                        <td>Родительский раздел *:</td>
                        <td>
                            <input type="hidden" name="old_parent_id" value="<?=$section['parent_id']?>" />
                            <SELECT name="parent_id" required>
                            <!--SELECT name="category_id"-->
                                <option value="0">Нет</option>
                                <?php
                                $count = count($sections);
                                for($i = 0; $i < $count; $i++)
                                {
                                    $cat = $sections[$i];
                                    echo '<option value="'.$cat['id'].'"';
                                    if((isset($_POST['parent_id']) && $_POST['parent_id'] == $cat['id']) || $section['parent_id'] == $cat['id']) echo ' selected';
                                    else if($this->session->userdata('parent_id') == $cat['id']) echo ' selected';
                                    
                                    echo '>'.$cat['name'].'</option>';
                                    /*
                                    $subs = $this->forum->getSubSections($cat['id']);
                                    if($subs)
                                    {
                                        $subcount = count($subs);
                                        for($j = 0; $j < $subcount; $j++)
                                        {
                                            $sub = $subs[$j];
                                            echo '<option value="'.$sub['id'].'"';
                                            if(isset($_POST['parent_id']) && $_POST['parent_id'] == $sub['id']) echo ' selected';
                                            else if($this->session->userdata('parent_id') == $sub['id']) echo ' selected';
                                            
                                            echo '>&nbsp;└&nbsp;'.$sub['name'].'</option>';
                                        }
                                    }
                                    */
                                }
                                ?>
                            </SELECT>
                        </td>
                    </tr>                    
                    <tr>
                        <td>Время:</td>
                        <td><input type="text" name="time" value="<?php if(isset($_POST['time'])) echo $_POST['time']; else echo $section['time'];?>" size="3" /></td>
                    </tr>
                    <tr>
                        <td>Дата:</td>
                        <td>
                            <input id="datepicker" type="text" name="date" value="<?php if(isset($_POST['date'])) echo $_POST['date']; else echo $section['date'];?>" />
                        </td>
                    </tr>                    
                    <tr>
                        <td>Краткое описание:</td>
                        <td><textarea name="descr"><?php if(isset($_POST['descr'])) echo $_POST['descr']; else echo $section['descr']; ?></textarea></td>
                    </tr>
                    <tr>
                        <td>title:</td>
                        <td><input type="text" name="title" size="50" value="<?php if(isset($_POST['title'])) echo $_POST['title']; else echo $section['title']; ?>" /></td>
                    </tr>
                    <tr>
                        <td>keywords:</td>
                        <td><textarea name="keywords"><?php if(isset($_POST['keywords'])) echo $_POST['keywords']; else echo $section['keywords']; ?></textarea></td>
                    </tr>
                    <tr>
                        <td>description:</td>
                        <td><textarea name="description"><?php if(isset($_POST['description'])) echo $_POST['description']; else echo $section['description']; ?></textarea></td>
                    </tr>
                    <tr>
                        <td>robots:</td>
                        <td><input type="text" name="robots" size="20" value="<?php if(isset($_POST['robots'])) echo $_POST['robots']; else echo $section['robots']; ?>" /></td>
                    </tr>
                    <tr>
                        <td>Кол-во просмотров:</td>
                        <td><input type="text" name="views" size="5" value="<?php if(isset($_POST['views'])) echo $_POST['views']; else echo $section['views']; ?>" /></td>
                    </tr>
                    <tr>
                        <td>SEO текст:</td>
                        <td><textarea name="seo" class="tinymce"><?php if(isset($_POST['seo'])) echo $_POST['seo']; else echo $section['seo']; ?></textarea></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="checkbox" name="active"<?php if($section['active'] == 1) echo ' checked' ?> /> Активный</td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="checkbox" name="create_topics"<?php if($section['create_topics'] == 1) echo ' checked' ?> /> Создавать темы в данном разделе</td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="submit" name="save" value="Сохранить" />&nbsp;<input type="submit" name="save_and_edit" value="Сохранить и вернуться к редактированию" /></td>
                    </tr>
                </table>
            </form>
            
        </td>
    </tr>
</table>
<?php
include("application/views/admin/footer.php");
?>