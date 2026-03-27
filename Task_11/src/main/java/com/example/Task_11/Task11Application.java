package com.example.Task_11;

import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.context.annotation.Bean;

@SpringBootApplication
public class Task11Application {

    public static void main(String[] args) {
        SpringApplication.run(Task11Application.class, args);
    }

    @Bean
    CommandLineRunner run(StudentRepository repository) {
        return args -> {

            repository.save(new Student(null, "Babu", 20, "CSE", "babu@gmail.com"));
            repository.save(new Student(null, "Ravi", 22, "ECE", "ravi@gmail.com"));
            repository.save(new Student(null, "Anu", 19, "CSE", "anu@gmail.com"));

        };
    }
}