package com.example.employee;

import com.example.employee.model.Employee;
import com.example.employee.service.EmployeeService;
import org.springframework.context.annotation.AnnotationConfigApplicationContext;

import java.util.List;

public class EmployeeApp {
    public static void main(String[] args) {
        System.out.println("Initializing Spring IoC Container...");
        AnnotationConfigApplicationContext context = new AnnotationConfigApplicationContext(AppConfig.class);

        EmployeeService employeeService = context.getBean(EmployeeService.class);

        System.out.println("\n--- Adding Employees ---");
        employeeService.addEmployee(new Employee(101, "Alice Johnson", "Engineering", 75000));
        employeeService.addEmployee(new Employee(102, "Bob Smith", "Marketing", 60000));
        employeeService.addEmployee(new Employee(103, "Charlie Davis", "Sales", 65000));

        System.out.println("\n--- Listing All Employees ---");
        List<Employee> allEmployees = employeeService.getAllEmployees();
        allEmployees.forEach(System.out::println);

        System.out.println("\n--- Searching for Employee by ID ---");
        int searchId = 102;
        employeeService.getEmployeeById(searchId).ifPresentOrElse(
            emp -> System.out.println("Found: " + emp),
            () -> System.out.println("Employee with ID " + searchId + " not found.")
        );

        context.close();
        System.out.println("\nSpring context closed.");
    }
}
