import java.util.ArrayList;
import java.util.Scanner;

class Student {
    private int roll;
    private String name;
    private double marks;

    Student(int r, String n, double m) {
        roll = r;
        name = n;
        marks = m;
    }

    int getRoll() {
        return roll;
    }

    void display() {
        System.out.println("Roll:" + roll + " Name:" + name + " Marks:" + marks);
    }
}

public class StudentRecord {
    public static void main(String[] args) {

        ArrayList<Student> students = new ArrayList<>();
        Scanner sc = new Scanner(System.in);

        while (true) {
            System.out.println("\n1.Add  2.Display  3.Search  4.Exit");
            int choice = sc.nextInt();

            if (choice == 1) {
                System.out.print("Enter Roll: ");
                int r = sc.nextInt();
                sc.nextLine();

                System.out.print("Enter Name: ");
                String n = sc.nextLine();

                System.out.print("Enter Marks: ");
                double m = sc.nextDouble();

                students.add(new Student(r, n, m));
                System.out.println("Student Added");
            }
            else if (choice == 2) {
                for (Student s : students)
                    s.display();
            }
            else if (choice == 3) {
                System.out.print("Enter Roll to Search: ");
                int r = sc.nextInt();
                boolean found = false;

                for (Student s : students) {
                    if (s.getRoll() == r) {
                        s.display();
                        found = true;
                    }
                }
                if (!found)
                    System.out.println("Not Found");
            }
            else if (choice == 4) {
                System.out.println("Exit");
                break;
            }
            else {
                System.out.println("Invalid Choice");
            }
        }
    }
}
