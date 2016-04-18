<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Fields\TextField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use RuntimeException;
use UnitTester;

class InputRendererCest
{
    public function handleAttributes(UnitTester $I)
    {
        $form = new InputRenderer();
        $field = new TextField('text');

        $I->assertSame('<input class="form-control foo bar" name="text" type="text"/>',
            $form->render($field, ['class' => ['foo', 'bar']]), 'folds multi-valued class attribute');
        $I->assertSame('<input class="form-control" name="text" readonly type="text"/>',
            $form->render($field, ['readonly' => true]), 'handles boolean TRUE attribute value');
        $I->assertSame('<input class="form-control" name="text" type="text"/>',
            $form->render($field, ['readonly' => false]), 'handles boolean FALSE attribute value');
        $I->assertSame('<input class="form-control" name="text" type="text"/>',
            $form->render($field, ['foo' => null]), 'filters NULL-value attributes');

        $form->xhtml = true;
        $I->assertSame('<input class="form-control" name="text" readonly="readonly" type="text"/>',
            $form->render($field, ['readonly' => true]), 'renders value-less attributes as valid XHTML');
        $form->xhtml = false;

        $I->assertSame('text', $form->getName($field), 'name without prefix');
        $I->assertSame(null, $form->getId($field), 'no id attribute when $id_prefix is NULL');

        $form->collection_name = 'form';
        $form->id_prefix = 'form';

        $I->assertSame('form[text]', $form->getName($field), 'name with prefix');
        $I->assertSame('form-text', $form->getId($field), 'id with defined prefix');

        $form->collection_name = ['form', 'subform'];
        $form->id_prefix = 'form-subform';
        $I->assertSame('form[subform][text]', $form->getName($field), 'renderer name with double prefix');
        $I->assertSame('form-subform-text', $form->getId($field), 'id for renderer name with double prefix');
    }

    public function visitNestedInputs(UnitTester $I)
    {
        $renderer = new InputRenderer(null, 'form', 'form');
        $parent = new TextField('parent');
        $child = new TextField('child');

        $renderer->visit($parent, function (InputModel $model) {
        });

        $I->assertSame([], $renderer->model->input, 'input remains empty after visiting');
        $I->assertSame([], $renderer->model->getErrors(), 'errors remain empty after visiting');

        $renderer->visit($parent, function (InputModel $model) use ($I, $renderer, $child) {
            $child->setValue($model, 'test');
            $model->setError($child, 'whoops');

            $I->assertSame($renderer->collection_name, ['form', 'parent'], 'name prefix added');
            $I->assertSame($renderer->id_prefix, 'form-parent', 'name prefix added');
        });

        $I->assertSame('form', $renderer->collection_name, 'name prefix restored');
        $I->assertSame('form', $renderer->id_prefix, 'id prefix restored');

        $I->assertSame(['parent' => ['child' => 'test']], $renderer->model->input,
            'child value merged to parent');
        $I->assertSame(['parent' => ['child' => 'whoops']], $renderer->model->getErrors(),
            'child errors merged to parent');

        $renderer->model = InputModel::create([123 => ['child' => 'hello']]);

        $renderer->visit(123, function (InputModel $model) use ($I, $child) {
            $I->assertSame($model->getInput($child), 'hello', 'can get child value from parent using scalar key');
            $child->setValue($model, 'world');
        });

        $I->assertSame([123 => ['child' => 'world']], $renderer->model->input,
            'child value merged to parent with scalar key');
    }

    public function mergeHTMLAttributes(UnitTester $I)
    {
        $renderer = new InputRenderer();

        $I->assertSame(['a' => '2'], $renderer->mergeAttrs(['a' => '1'], ['a' => '2']));

        $I->assertSame(['a' => '1', 'b' => '2'], $renderer->mergeAttrs(['a' => '1'], ['b' => '2']));

        $I->assertSame(['a' => '1', 'class' => 'foo'], $renderer->mergeAttrs(['a' => '1', 'class' => 'foo']));

        $I->assertSame(['class' => ['foo', 'bar']],
            $renderer->mergeAttrs(['class' => 'foo'], ['class' => 'bar']));
    }

    public function renderHTMLTags(UnitTester $I)
    {
        $renderer = new InputRenderer();

        $I->assertSame('<input type="text"/>', $renderer->tag('input', ['type' => 'text']), 'self-closing tag');

        $I->assertSame('<div>Foo &amp; Bar</div>', $renderer->tag('div', [], 'Foo &amp; Bar'),
            'tag with inner HTML');

        $I->assertSame('<script></script>', $renderer->tag('script', [], ''), 'empty tag');

        $I->assertSame('<div>', $renderer->openTag('div'), 'open tag');

        $I->assertSame('', $renderer->attrs(['a' => false, 'b' => null, 'c' => []]),
            'filters FALSE, NULL and empty array() attributes');

        $I->assertSame(' a=""', $renderer->attrs(['a' => '']), 'does not filter empty string attribute');

        $I->assertSame(' a="foo" b="bar"', $renderer->attrs(['a' => 'foo', 'b' => 'bar']), 'adds a leading space');
    }

    public function renderContainerTags(UnitTester $I)
    {
        $form = new InputRenderer();
        $field = new TextField('text');

        $I->assertSame('<div data-foo="bar">foo &amp; bar</div>',
            $form->divFor($field, 'foo &amp; bar', ['data-foo' => 'bar']));

        $I->assertSame('<div data-div="bar"><input class="form-control" data-input="foo" name="text" type="text"/></div>',
            $form->renderDiv($field, ['data-input' => 'foo'], ['data-div' => 'bar']));

        $field->setRequired(true);

        $form->model->setError($field, 'whoops');

        $I->assertSame('<div class="required has-error" data-foo="bar">foo &amp; bar</div>',
            $form->divFor($field, 'foo &amp; bar', ['data-foo' => 'bar']));

        $I->assertSame('<div class="required has-error foo bar"><input class="form-control foo" name="text" required type="text"/></div>',
            $form->renderDiv($field, ['class' => 'foo'], ['class' => ['foo', 'bar']]));
    }

    public function renderInputGroups(UnitTester $I)
    {
        $form = new InputRenderer();
        $field = new TextField('text');

        $I->assertSame('<div class="form-group"></div>', $form->group() . $form->endGroup());

        $I->assertSame('<div class="form-group"></div>', $form->groupFor($field) . $form->endGroup());

        $form->model->setError($field, 'some error');

        $I->assertSame('<div class="form-group has-error">', $form->groupFor($field));

        $field->setRequired(true);

        $I->assertSame('<div class="form-group required has-error">', $form->groupFor($field));

        $I->assertSame('<div class="form-group required has-error foo">', $form->groupFor($field, ['class' => 'foo']),
            'merge with one class');

        $I->assertSame('<div class="form-group required has-error foo bar">',
            $form->groupFor($field, ['class' => ['foo', 'bar']]), 'merge with multiple classes');
    }

    public function renderLabelTags(UnitTester $I)
    {
        $form = new InputRenderer();
        $field = new TextField('text');

        $I->assertException(
            RuntimeException::class,
            'cannot produce a label when FormHelper::$id_prefix is NULL',
            function () use ($form, $field) {
                $form->labelFor($field);
            }
        );

        $form = new InputRenderer(null, 'form');

        $I->assertException(
            RuntimeException::class,
            'the given Field has no defined label',
            function () use ($form, $field) {
                $form->labelFor($field);
            }
        );

        $field->setLabel('Name');

        $form->label_suffix = ':';

        $I->assertSame('<label class="control-label" for="form-text">Name:</label>', $form->labelFor($field));

        $I->assertSame('<label class="control-label" for="form-text">Nombre:</label>', $form->labelFor($field, "Nombre"));
    }

    public function buildLabel(UnitTester $I)
    {
        $form = new InputRenderer();

        $I->assertSame('<label class="control-label" for="foo">Hello</label>', $form->label("foo", "Hello"));
        $I->assertSame('<label class="control-label stuff" for="foo">Hello</label>', $form->label("foo", "Hello", ["class" => "stuff"]));
    }

    public function buildLabeledInputGroups(UnitTester $I)
    {
        $form = new InputRenderer(null, 'form');

        $field = new TextField('hello');
        $field->setLabel('Hello!');

        $form->model->input['hello'] = 'World';

        $I->assertSame('<div class="form-group"><label class="control-label" for="form-hello">Hello!</label><input class="form-control" id="form-hello" name="form[hello]" type="text" value="World"/></div>',
            $form->renderGroup($field));
    }

    public function overridePlaceholders(UnitTester $I)
    {
        $form = new InputRenderer();

        $field = new TextField("test");

        $form->setPlaceholder($field, "Hello");

        $I->assertSame('<input class="form-control" name="test" placeholder="Hello" type="text"/>', $form->render($field));
    }

    public function overrideRequired(UnitTester $I)
    {
        $form = new InputRenderer();

        $field = new TextField("test");

        $form->setRequired($field);

        $I->assertSame('<div class="required"><input class="form-control" name="test" required type="text"/></div>', $form->renderDiv($field));
    }

    public function buildInput(UnitTester $I)
    {
        $form = new InputRenderer();

        $I->assertSame('<input data-bat="baz" name="foo" type="hidden" value="bar"/>', $form->input("hidden", "foo", "bar", ["data-bat" => "baz"]));
    }
}
