<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Form;

use App\Entity\TermOfUse;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TermOfUse form.
 */
class TermOfUseType extends AbstractType {
    /**
     * Add form fields to $builder.
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void {
        $builder->add('weight', null, [
            'label' => 'Weight',
            'required' => true,
            'attr' => [
                'help_block' => '',
            ],
        ]);
        $builder->add('keyCode', TextType::class, [
            'label' => 'Key Code',
            'required' => true,
            'attr' => [
                'help_block' => '',
            ],
        ]);
        $builder->add('langCode', TextType::class, [
            'label' => 'Lang Code',
            'required' => true,
            'attr' => [
                'help_block' => '',
            ],
        ]);
        $builder->add('content', TextareaType::class, [
            'label' => 'Content',
            'required' => true,
            'attr' => [
                'help_block' => '',
                'class' => 'tinymce',
            ],
        ]);
    }

    /**
     * Define options for the form.
     *
     * Set default, optional, and required options passed to the
     * buildForm() method via the $options parameter.
     */
    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => TermOfUse::class,
        ]);
    }
}
