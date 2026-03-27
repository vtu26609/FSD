package com.example.Task_11;

import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.data.domain.Page;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/students")
public class StudentController {

    @Autowired
    private StudentService studentService;

    @PostMapping
    public Student saveStudent(@RequestBody Student student) {
        return studentService.saveStudent(student);
    }

    @GetMapping
    public List<Student> getAllStudents() {
        return studentService.getAllStudents();
    }

    @GetMapping("/department/{dept}")
    public List<Student> getByDepartment(@PathVariable String dept) {
        return studentService.getByDepartment(dept);
    }

    @GetMapping("/age/{age}")
    public List<Student> getByAge(@PathVariable int age) {
        return studentService.getByAge(age);
    }

    @GetMapping("/sort/name")
    public List<Student> sortByName() {
        return studentService.sortByName();
    }

    @GetMapping("/sort/age")
    public List<Student> sortByAge() {
        return studentService.sortByAge();
    }

    @GetMapping("/page")
    public Page<Student> getByPage(@RequestParam int page,
                                   @RequestParam int size) {
        return studentService.getByPage(page, size);
    }
}