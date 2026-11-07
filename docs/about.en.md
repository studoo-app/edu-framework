# Learning objectives

## Framework

**A first step towards teaching a framework (Symfony, Laravel ...)**

Introducing a framework such as Symfony or Laravel shows how MVC concepts, OOP and best practices are applied in a professional development environment.

- Start by explaining the framework's architecture and its request/response cycle, routing system, etc.
- Teach how to create models, views and controllers in the context of the framework.
- Show how to use the built-in tools for security, forms, validation, etc.
- Explain how to configure and customize the framework to meet the project's needs.

## MVC architecture

**Approaching a project layer by layer through the MVC (Model View Controller) architecture concept**

MVC architecture is a design pattern that splits an application into three main components: the model, the view and the controller. This separation helps organize the code, makes it more modular, and makes applications easier to maintain and scale.

- **Model**: Represents the data structure. The model handles business rules, database queries and data operations.
- **View**: Presents the data to the user. The view is the visual representation of the data, usually the user interfaces.
- **Controller**: Acts as an intermediary between the model and the view. The controller processes user input, manipulates data through the model and sends the output to the view.

## OOP and best practices

**Building a "fully OOP" project that follows the "best practices" expected by companies**

Object-oriented programming (OOP) is a programming method that uses classes and objects. It structures code so that it is easier to understand, maintain and reuse.

**Orchestrating with a dependency manager (Composer)**

Composer is a dependency management tool for PHP. It lets you declare the libraries your project depends on and manage them (install/update).

- Use the `composer.json` file to declare dependencies.
- Run `composer install` to install the specified dependencies.
- Use PSR-4 autoloading to load your classes automatically without manual `require` or `include` statements.
- Regularly review dependencies to make sure they are up to date and secure.
- How to choose quality, well-maintained dependencies.

**Writing unit tests (PHPUnit)**

PHPUnit is a unit testing framework for PHP. Unit tests check isolated parts of the code to make sure they work as expected.

- Write tests that cover both normal use cases and edge cases.
- Run the tests regularly to make sure changes do not introduce regressions.
- Integrate unit tests into a CI pipeline to automate testing.

## DevOps

**DevOps option (Docker, Deployer ...)**

An introduction to DevOps and tools such as Docker and Deployer helps students understand the importance of automation and infrastructure as code.

- **Docker**: Teach how to build containers for development and production environments, making deployment and scalability easier.
- **Deployer**: Show how to automate application deployment to different servers (through CI/CD pipelines).

These learning objectives provide a solid foundation for students in application development and application architecture, preparing them for the challenges of modern development and the expectations of the professional world.


!!! warning "Important information"

    **This framework is not suitable for production use. It is intended for educational purposes.**

